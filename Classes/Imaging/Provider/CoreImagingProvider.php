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
 * Last modified: 2020.04.01 at 20:08
 */

namespace LaborDigital\Typo3FrontendApi\Imaging\Provider;


use LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService;
use LaborDigital\Typo3BetterApi\FileAndFolder\FileInfo\FileInfo;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingContext;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\FileSystem\Fs;
use TYPO3\CMS\Core\Resource\Exception\InvalidHashException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use WebPConvert\WebPConvert;

/**
 * Class CoreImagingProvider
 *
 * @package LaborDigital\Typo3FrontendApi\Imaging\Provider
 */
class CoreImagingProvider extends AbstractImagingProvider
{

    /**
     * @var \TYPO3\CMS\Core\Resource\ProcessedFileRepository
     */
    protected $fileRepository;

    /**
     * @var \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService
     */
    protected $falFileService;

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * CoreImagingProvider constructor.
     *
     * @param   \TYPO3\CMS\Core\Resource\ProcessedFileRepository                      $fileRepository
     * @param   \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService             $falFileService
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(
        ProcessedFileRepository $fileRepository,
        FalFileService $falFileService,
        FrontendApiConfigRepository $configRepository
    ) {
        $this->fileRepository   = $fileRepository;
        $this->falFileService   = $falFileService;
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritDoc
     */
    public function process(array $definition, FileInfo $fileInfo, ImagingContext $context): void
    {
        // Process the file
        $fileOrReference = $fileInfo->isFileReference() ? $fileInfo->getFileReference() : $fileInfo->getFile();
        $processed       = $this->falFileService->getResizedImage($fileOrReference, $definition);
        if (! $processed->exists()) {
            throw new ImagingException('File was not found on the file system', 404);
        }

        // Dump the redirect file
        $realUrl               = '/' . $processed->getPublicUrl(false);
        $this->defaultRedirect = $realUrl;

        // Handle web-p generation
        if (in_array(strtolower($processed->getExtension()), ['png', 'webp', 'jpg', 'jpeg'])) {
            // Create a new processed file for the webp storage
            try {
                $definition['hash'] = $processed->getSha1();
            } catch (InvalidHashException $exception) {
                // The file was not found on the hard drive
                throw new ImagingException('File was not found on the file system', 404);
            }
            $definition['asWebP'] = true;
            $processedWebp        = $this->fileRepository->findOneByOriginalFileAndTaskTypeAndConfiguration(
                $fileInfo->getFile(), ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $definition);

            // Generate the new webp image and store it as processed file
            if ($processedWebp->isNew()) {
                // Set executable directories based on TYPO3 config
                $processor = Arrays::getPath($GLOBALS, 'TYPO3_CONF_VARS.GFX.processor');
                if (! empty($processor)) {
                    if ($processor === 'ImageMagick') {
                        if (! defined('WEBPCONVERT_IMAGEMAGICK_PATH')) {
                            define('WEBPCONVERT_IMAGEMAGICK_PATH',
                                Arrays::getPath($GLOBALS, 'TYPO3_CONF_VARS.GFX.processor_path') . 'convert');
                        }
                    } elseif ($processor === 'GraphicsMagick') {
                        if (! defined('WEBPCONVERT_GRAPHICSMAGICK_PATH')) {
                            define('WEBPCONVERT_GRAPHICSMAGICK_PATH',
                                Arrays::getPath($GLOBALS, 'TYPO3_CONF_VARS.GFX.processor_path') . 'gm');
                        }
                    }
                }

                // Generate the webp version of the file
                $tmpFile = sys_get_temp_dir() . '/' . md5($processed->getIdentifier()) . '.' . $processed->getExtension();
                Fs::writeFile($tmpFile, $processed->getContents());
                $tmpFileOut = $tmpFile . '.webp';
                WebPConvert::convert($tmpFile, $tmpFileOut,
                    $this->configRepository->tool()->get('imaging.options.webPConverterOptions', []));

                // Inherit the required properties from the parent
                $props = $processed->getProperties();
                unset($props['uid'], $props['tstamp'], $props['crdate'],
                    $props['configuration'], $props['name'], $props['identifier']);
                $processedWebp->updateProperties($props);
                $processedWebp->setName($processed->getName() . '.webp');
                $processedWebp->updateWithLocalFile($tmpFileOut);

                // Add the file to the database
                $this->fileRepository->add($processedWebp);
                Fs::remove($tmpFileOut);
                Fs::remove($tmpFile);
            }

            // Dump the redirect file
            $realUrl            = '/' . $processedWebp->getPublicUrl(false);
            $this->webPRedirect = $realUrl;
        }
    }

}
