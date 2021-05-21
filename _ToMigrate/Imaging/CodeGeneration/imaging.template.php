<?php
/** @noinspection AutoloadingIssuesInspection */
declare(strict_types=1);

/**
 * Copyright 2020 LABOR.digital
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2020.04.01 at 20:29
 */

use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingApplication;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingContext;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Disable speaking errors
error_reporting(0);

// Compiled variables
define('FRONTEND_API_IMAGING_ENTRY_POINT', true);
define('FRONTEND_API_IMAGING_SHOW_ERRORS', false);
define('FRONTEND_API_IMAGING_HOST', '@@FAI_HOST@@');
define('FRONTEND_API_IMAGING_REDIRECT_DIR', '@@FAI_REDIRECT_DIR@@');
define('FRONTEND_API_IMAGING_VENDOR_DIR', '@@FAI_VENDOR_DIR@@');
define('FRONTEND_API_IMAGING_ENTRY_POINT_DEPTH', '@@FAI_EPD@@');

// Compiled variables END

class ImagingRequest
{

    /**
     * The name of the required file
     *
     * @var string
     */
    public $file;

    /**
     * The numeric uid of the requested file/file reference
     *
     * @var int
     */
    public $uid;

    /**
     * Cache busting hash of the file contents
     *
     * @var string
     */
    public $hash;

    /**
     * The image processing definition, required for the file
     *
     * @var string
     */
    public $definition;

    /**
     * The crop definition to apply for the file
     *
     * @var string|null
     */
    public $crop;

    /**
     * True if the image should be rendered in twice the size (retina displays)
     *
     * @var bool
     */
    public $isX2;

    /**
     * Contains the type of the request either "file" or "reference".
     * To determine if a file reference or fal file was required
     *
     * @var string
     */
    public $type;

    /**
     * True if the browser accepts webP images
     *
     * @var bool
     */
    public $acceptsWebP;

    /**
     * The absolute path to the redirect hash file
     *
     * @var string
     */
    public $redirectHashPath;

    /**
     * The absolute path to the file containing the redirect url for a file
     *
     * @var string
     */
    public $redirectInfoPath;
}

class ImagingHandler
{

    /**
     * Runs the low level imaging handler
     */
    public function run(): void
    {
        $request = $this->readAndValidateRequest();
        $this->prepareRedirectInformation($request);

        // Check if we have to generate the redirect information
        if (! is_file($request->redirectInfoPath) || ! is_file($request->redirectHashPath)) {
            $this->runImagingApplication($request);
        }

        // Check if the file exists now
        if (is_file($request->redirectInfoPath)) {
            $this->executeRedirect($request);
        }

        $this->error(404);
    }

    /**
     * Builds the request object based on the current browser request
     *
     * @return \ImagingRequest
     */
    protected function readAndValidateRequest(): ImagingRequest
    {
        $request = new ImagingRequest();

        // Get the image
        if (empty($_GET['file'])) {
            $this->error(400, 'Missing \"file\" parameter!');
        }
        if (! is_string($_GET['file'])) {
            $this->error(400, 'Invalid \"file\" parameter!');
        }
        $request->file = $_GET['file'];

        // Extract the id and type from the requested file
        $fileParts = explode('.', $request->file);
        if (count($fileParts) !== 4) {
            $this->error(400, 'Invalid file given! Three parts are expected!');
        }
        $request->hash = preg_replace('~[^a-fA-F0-9]~', '', $fileParts[1]);
        if (strlen($request->hash) !== 32) {
            $this->error(400, 'Invalid hash given!');
        }
        $id = $fileParts[2];
        if (strlen($id) < 2) {
            $this->error(400, 'Invalid file given! The id part is to short!');
        }
        $request->type = $id[0] === 'r' ? 'reference' : 'file';
        $request->uid  = (int)substr($id, 1);
        if (empty($request->uid)) {
            $this->error(404, 'Empty or invalid uid given!');
        }

        // Get the definition
        $request->definition = ! empty($_GET['definition']) && is_string($_GET['definition']) ? $_GET['definition'] : 'default';

        // Get the crop value
        $request->crop = ! empty($_GET['crop']) && is_string($_GET['crop']) ? $_GET['crop'] : null;

        // Check if an x2 image is requested
        $request->isX2 = ! empty($_GET['x2']) && is_string($_GET['x2']) &&
                         in_array(strtolower($_GET['x2']), ['true', '1', 'yes', 'on'], true);

        // Check if the browser can handle the .webp format
        $request->acceptsWebP = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;

        return $request;
    }

    /**
     * Generated the storage paths for the redirect information files
     *
     * @param   \ImagingRequest  $request
     */
    protected function prepareRedirectInformation(ImagingRequest $request): void
    {
        $redirectInfoPath = FRONTEND_API_IMAGING_REDIRECT_DIR;
        $idHash           = md5($request->type . '-' . $request->uid);
        $redirectInfoPath .= $idHash[0] . '/' . $idHash[1] . '/' . $idHash[2] . '/';
        $redirectInfoPath .= $request->type . '-' . $request->uid . ($request->isX2 ? '-x2' : '') . '/';
        $redirectHashPath = $redirectInfoPath . $request->hash;
        $redirectInfoPath .= preg_replace('~[^a-zA-Z0-9\-_]~', '',
            $request->definition . rtrim('-' . $request->crop, '-'));
        $redirectInfoPath .= '-' . md5($request->definition . '.' . $request->crop);

        $request->redirectHashPath = $redirectHashPath;
        $request->redirectInfoPath = $redirectInfoPath;
    }

    /**
     * Runs the imaging application in order to create a new, processed file for the request
     *
     * @param   \ImagingRequest  $request
     */
    protected function runImagingApplication(ImagingRequest $request): void
    {
        $composerFile = FRONTEND_API_IMAGING_VENDOR_DIR . 'autoload.php';

        if (! is_file($composerFile)) {
            $this->error(500, 'Could not find composer autoload.php');
        }

        $classLoader = require $composerFile;
        SystemEnvironmentBuilder::run((int)FRONTEND_API_IMAGING_ENTRY_POINT_DEPTH,
            SystemEnvironmentBuilder::REQUESTTYPE_AJAX);
        Bootstrap::init($classLoader);

        // Provide a dummy TSFE
        if (empty($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE'] = new stdClass();
        }

        TypoContainer::getInstance()->get(ImagingApplication::class)
                     ->run(static function () use ($request): ImagingContext {
                         return GeneralUtility::makeInstance(ImagingContext::class, $request);
                     });
    }

    /**
     * Handles the redirect to the real file based on the stored information
     *
     * @param   \ImagingRequest  $request
     */
    protected function executeRedirect(ImagingRequest $request): void
    {
        // Check if we are able to handle via a webp file
        $redirectInfoPath = $request->redirectInfoPath;
        if ($request->acceptsWebP && is_file($redirectInfoPath . '-webp')) {
            $redirectInfoPath .= '-webp';
        }

        $redirectTarget = @file_get_contents($redirectInfoPath);
        if (! $redirectTarget) {
            $this->error(404);
        }
        if ($redirectTarget[0] === '/') {
            $redirectTarget = FRONTEND_API_IMAGING_HOST . $redirectTarget;
        }

        header('Location: ' . $redirectTarget, true, 301);
        exit();
    }

    /**
     * Helper to handle an error while processing the imaging request
     *
     * @param   int          $code     The http status code
     * @param   string|null  $msg      Optional message for the user to see
     * @param   string|null  $httpMsg  Optional http message otherwise inferred from the status code
     */
    public function error(int $code, ?string $msg = null, ?string $httpMsg = null): void
    {
        $httpMsgList = [404 => 'Not Found', 500 => 'Internal Server Error', 400 => 'Bad Request'];
        if (empty($httpMsg)) {
            $httpMsg = $httpMsgList[$code] ?? 'Internal Server Error';
        }
        header('HTTP/1.0 ' . $code . ' ' . $httpMsg);
        http_response_code($code);
        die(empty($msg) ? $httpMsg : $msg);
    }
}

(new ImagingHandler())->run();
