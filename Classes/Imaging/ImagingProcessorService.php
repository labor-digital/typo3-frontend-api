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
 * Last modified: 2020.04.01 at 12:11
 */

namespace LaborDigital\Typo3FrontendApi\Imaging;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\Event\TypoEventBus;
use LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService;
use LaborDigital\Typo3FrontendApi\Event\ImagingPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\Imaging\Provider\ImagingProviderInterface;
use Neunerlei\FileSystem\Fs;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;

class ImagingProcessorService
{

    /**
     * @var \LaborDigital\Typo3BetterApi\Container\TypoContainer
     */
    protected $container;

    /**
     * @var \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService
     */
    protected $falFileService;

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * @var \LaborDigital\Typo3BetterApi\Event\TypoEventBus
     */
    protected $eventBus;

    /**
     * ImagingProcessorService constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Container\TypoContainer                  $container
     * @param   \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService             $falFileService
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(
        TypoContainer $container,
        FalFileService $falFileService,
        FrontendApiConfigRepository $configRepository,
        TypoEventBus $eventBus
    ) {
        $this->container        = $container;
        $this->falFileService   = $falFileService;
        $this->configRepository = $configRepository;
        $this->eventBus         = $eventBus;
    }

    /**
     * Processes the given imaging context by creating the required processed files and their matching redirect configuration
     *
     * @param   \LaborDigital\Typo3FrontendApi\Imaging\ImagingContext  $context
     *
     * @throws \LaborDigital\Typo3FrontendApi\Imaging\ImagingException
     */
    public function process(ImagingContext $context)
    {
        // Prepare the definition
        $definitions = $this->configRepository->tool()->get("imaging.definitions", []);
        if (! isset($definitions[$context->getDefinitionKey()])) {
            throw new ImagingException("Invalid definition key given", 400);
        }
        $definition = $definitions[$context->getDefinitionKey()];

        // Prepare x2 definition
        if ($context->isX2()) {
            foreach ($definition as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                if (! in_array($k, ["width", "height", "maxWidth", "maxHeight", "minWidth", "minHeight"])) {
                    continue;
                }
                $definition[$k] = preg_replace_callback("~[0-9.,]+~si", function ($m) {
                    $v = floatval(str_replace(",", ".", $m[0]));

                    return ($v * 2) . "";
                }, $v);
            }
        }

        // Resolve the file / file reference based on the id
        try {
            $fileOrReference = $context->getType() === "reference"
                ? $this->falFileService->getFileReference($context->getUid())
                :
                $this->falFileService->getFile($context->getUid());
        } catch (ResourceDoesNotExistException $e) {
        }
        if (empty($fileOrReference)) {
            throw new ImagingException("File was not found in FAL", 404);
        }
        $fileInfo = $this->falFileService->getFileInfo($fileOrReference);
        if (! $fileInfo->isImage()) {
            throw new ImagingException("The requested file is not an image", 404);
        }

        // Validate the hash
        if (! Fs::exists($context->getRedirectHashPath())) {
            $givenHash = basename($context->getRedirectHashPath());
            $realHash  = md5($fileInfo->getHash() . \GuzzleHttp\json_encode($fileInfo->getImageInfo()->getCropVariants()));

            // If the hashes do match -> a new crop was created or the image changed -> flush the directory
            if ($givenHash === $realHash) {
                $dirName = dirname($context->getRedirectHashPath());
                Fs::flushDirectory($dirName);
                Fs::mkdir($dirName);
                Fs::touch($context->getRedirectHashPath());
            } else {
                // The hashes don't match, does the redirect file exist?
                // Yes -> Okay handle the file like normally -> outdated link...
                if (Fs::exists($context->getRedirectPath())) {
                    return;
                }
            }
        }

        // Select the best matching crop variant
        $cropVariants = $fileInfo->getImageInfo()->getCropVariants();
        if (isset($definition["crop"]) && isset($cropVariants[$definition["crop"]])) {
            $crop = $definition["crop"];
        } else {
            $crop = isset($cropVariants["default"]) ? "default" : null;
        }
        if (! empty($context->getCrop())) {
            $givenCrop = $context->getCrop();
            if ($givenCrop === "none") {
                $crop = false;
            } elseif (isset($cropVariants[$givenCrop])) {
                $crop = $givenCrop;
            }
        }
        if (! is_null($crop)) {
            $definition["crop"] = $crop;
        }

        // Create and execute the provider
        $providerClass = $this->configRepository->tool()->get("imaging.options.imagingProvider");
        $provider      = $this->container->get($providerClass);
        if (! $provider instanceof ImagingProviderInterface) {
            throw new ImagingException("The provider does not implement the required interface!");
        }
        $provider->process($definition, $fileInfo, $context);

        // Allow post processing
        $this->eventBus->dispatch(($e = new ImagingPostProcessorEvent(
            $definition, $fileInfo, $context, $provider->getDefaultRedirect(), $provider->getWebPRedirect()
        )));

        // Write the redirect files
        $defaultRedirect = $e->getDefaultRedirect();
        Fs::writeFile($context->getRedirectPath(), $defaultRedirect);
        $webPRedirect = $e->getWebPRedirect();
        if (! empty($webPRedirect)) {
            Fs::writeFile($context->getRedirectPath() . "-webp", $webPRedirect);
        }
    }
}
