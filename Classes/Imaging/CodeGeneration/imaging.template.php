<?php
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

// Disable speaking errors
error_reporting(0);

// Compiled variables
define("FRONTEND_API_IMAGING_ENTRY_POINT", true);
define("FRONTEND_API_IMAGING_HOST", "@@FAI_HOST@@");
define("FRONTEND_API_IMAGING_REDIRECT_DIR", "@@FAI_REDIRECT_DIR@@");
define("FRONTEND_API_IMAGING_VENDOR_DIR", "@@FAI_VENDOR_DIR@@");
define("FRONTEND_API_IMAGING_ENTRY_POINT_DEPTH", "@@FAI_EPD@@");
// Compiled variables END

/**
 * Helper to handle an error while processing the imaging request
 *
 * @param   int          $code     The http status code
 * @param   string|null  $msg      Optional message for the user to see
 * @param   string|null  $httpMsg  Optional http message otherwise inferred from the status code
 */
function _imagingError(int $code, ?string $msg = null, ?string $httpMsg = null): void
{
    $httpMsgList = [404 => "Not Found", 500 => "Internal Server Error", 400 => "Bad Request"];
    if (empty($httpMsg)) {
        $httpMsg = isset($httpMsgList[$code]) ? $httpMsgList[$code] : "Internal Server Error";
    }
    header("HTTP/1.0 $code $httpMsg");
    http_response_code($code);
    die(empty($msg) ? $httpMsg : $msg);
}

// Call the handler
call_user_func(function () {
    // Get the image
    if (empty($_GET["file"])) {
        _imagingError(400, "Missing \"file\" parameter!");
    }
    if (! is_string($_GET["file"])) {
        _imagingError(400, "Invalid \"file\" parameter!");
    }
    $file = $_GET["file"];

    // Get the definition
    $definition = ! empty($_GET["definition"]) && is_string($_GET["definition"]) ? $_GET["definition"] : "default";

    // Get the crop value
    $crop = ! empty($_GET["crop"]) && is_string($_GET["crop"]) ? $_GET["crop"] : null;

    // Check if an x2 image is requested
    $isX2 = ! empty($_GET["x2"]) && is_string($_GET["x2"]) && in_array(strtolower($_GET["x2"]), ["true", "1", "yes", "on"]);

    // Extract the id and type from the requested file
    $fileParts = explode(".", $file);
    if (count($fileParts) !== 4) {
        _imagingError(400, "Invalid file given! Three parts are expected!");
    }
    $hash = preg_replace("~[^a-fA-F0-9]~", "", $fileParts[1]);
    if (strlen($hash) !== 32) {
        _imagingError(400, "Invalid hash given!");
    }
    $id = $fileParts[2];
    if (strlen($id) < 2) {
        _imagingError(400, "Invalid file given! The id part is to short!");
    }
    $type = $id[0] === "r" ? "reference" : "file";
    $uid  = (int)substr($id, 1);
    if (empty($uid)) {
        _imagingError(404, "Empty or invalid uid given!");
    }

    // Check if the browser can handle the .webp format
    $acceptsWebP = isset($_SERVER["HTTP_ACCEPT"]) && strpos($_SERVER["HTTP_ACCEPT"], "image/webp") !== false;

    // Try to extract the redirect path from the local storage
    $redirectInfoPath   = FRONTEND_API_IMAGING_REDIRECT_DIR;
    $idHash             = md5($type . "-" . $uid);
    $redirectInfoPath   .= $idHash[0] . "/" . $idHash[1] . "/" . $idHash[2] . "/";
    $redirectInfoPath   .= $type . "-" . $uid . ($isX2 ? "-x2" : "") . "/";
    $redirectHashPath   = $redirectInfoPath . $hash;
    $redirectInfoPath   .= preg_replace("~[^a-zA-Z0-9\-_]~", "", $definition . rtrim("-" . $crop, "-"));
    $redirectInfoPath   .= "-" . md5($definition . "." . $crop);
    $redirectInfoExists = is_file($redirectInfoPath);
    $redirectHashExists = $redirectInfoExists && file_exists($redirectHashPath);
    if (! $redirectInfoExists || ! $redirectHashExists) {
        // Build redirect info for the file
        call_user_func(static function (
            string $type,
            int $uid,
            string $redirectInfoPath,
            string $redirectHashPath,
            string $definition,
            ?string $crop,
            bool $isX2
        ) {
            $composerFile = FRONTEND_API_IMAGING_VENDOR_DIR . "autoload.php";
            if (! is_file($composerFile)) {
                _imagingError(500, "Could not find composer autoload.php");
            }
            $classLoader = require $composerFile;
            SystemEnvironmentBuilder::run(FRONTEND_API_IMAGING_ENTRY_POINT_DEPTH,
                SystemEnvironmentBuilder::REQUESTTYPE_FE);
            Bootstrap::init($classLoader);
            $container = TypoContainer::getInstance();
            $container->get(ImagingApplication::class, [
                "args" => [
                    $container->get(ImagingContext::class, [
                        "args" => [
                            $type,
                            $uid,
                            $redirectInfoPath,
                            $redirectHashPath,
                            $definition,
                            $crop,
                            $isX2,
                        ],
                    ]),
                ],
            ])->run();
        }, $type, $uid, $redirectInfoPath, $redirectHashPath, $definition, $crop, $isX2);
    }

    // Check if the file exists now
    if ($redirectInfoExists || is_file($redirectInfoPath)) {
        // Check if we are able to handle via a webp file
        if ($acceptsWebP && file_exists($redirectInfoPath . "-webp")) {
            $redirectInfoPath = $redirectInfoPath . "-webp";
        }
        $redirectTarget = file_get_contents($redirectInfoPath);
        if ($redirectTarget[0] === "/") {
            $redirectTarget = FRONTEND_API_IMAGING_HOST . $redirectTarget;
        }
        header("Location: " . $redirectTarget, true, 301);
        exit();
    }

    // Not found
    _imagingError(404);
});
