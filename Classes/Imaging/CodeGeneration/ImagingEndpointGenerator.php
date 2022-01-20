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
 * Last modified: 2020.04.01 at 20:07
 */

namespace LaborDigital\Typo3FrontendApi\Imaging\CodeGeneration;


use LaborDigital\Typo3BetterApi\Link\LinkService;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\FileSystem\Fs;
use Neunerlei\PathUtil\Path;

class ImagingEndpointGenerator
{

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $context;

    /**
     * @var \LaborDigital\Typo3BetterApi\Link\LinkService
     */
    protected $linkService;

    /**
     * ImagingEndpointGenerator constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext                  $context
     * @param   \LaborDigital\Typo3BetterApi\Link\LinkService                         $linkService
     */
    public function __construct(
        FrontendApiConfigRepository $configRepository,
        TypoContext $context,
        LinkService $linkService
    ) {
        $this->configRepository = $configRepository;
        $this->context          = $context;
        $this->linkService      = $linkService;
    }

    /**
     * Generates the endpoint php in the root directory of the fileadmin location
     */
    public function generate(): void
    {
        // Ignore if the imaging endpoint is disabled
        if (empty($this->configRepository->tool()->get('imaging', false))) {
            return;
        }

        // Gather the relevant variables
        $hostname     = $this->linkService->getHost();
        $varDir       = $this->configRepository->tool()->get('imaging.options.redirectDirectoryPath');
        $vendorDir    = $this->context->Path()->getVendorPath();
        $endpointPath = $this->configRepository->tool()->get('imaging.options.endpointDirectoryPath');
        $docRootDir   = $this->context->Path()->getPublicPath();
        $useProxy     = $this->configRepository->tool()->get('imaging.options.useProxyInsteadOfRedirect', false) ? 'true' : 'false';

        // Calculate entry point depth
        $endpointPathRelative = Path::makeRelative($endpointPath, $this->context->Path()->getPublicPath());
        $endpointPathParts    = array_filter(explode('/', $endpointPathRelative));
        $entryPointDepth      = count($endpointPathParts);

        // Load the template and apply the placeholders
        $tpl = Fs::readFile(__DIR__ . '/imaging.template.php');
        $tpl = str_replace(['@@FAI_HOST@@', '@@FAI_REDIRECT_DIR@@', '@@FAI_VENDOR_DIR@@', '@@FAI_EPD@@', '@@FAI_DOCROOT_DIR@@', '@@FAI_USE_PROXY@@'],
            [$hostname, $varDir, $vendorDir, $entryPointDepth, $docRootDir, $useProxy], $tpl);

        // Write the endpoint file at the configured location
        $endpointPath = Path::join($endpointPath, 'imaging.php');
        Fs::writeFile($endpointPath, $tpl);
    }
}
