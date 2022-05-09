<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.06.24 at 11:27
 */

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
 * Last modified: 2020.04.01 at 20:08
 */

namespace LaborDigital\T3fa\Core\Imaging\Processor;

use LaborDigital\T3ba\Tool\Fal\FalService;
use LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo;
use LaborDigital\T3fa\Core\Imaging\Request;
use LaborDigital\T3fa\Event\Imaging\CoreImagingPostProcessorEvent;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\FileSystem\Fs;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Resource\Exception\InvalidHashException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use WebPConvert\WebPConvert;

class CoreImagingProcessor extends AbstractImagingProcessor
{
    
    /**
     * @var \TYPO3\CMS\Core\Resource\ProcessedFileRepository
     */
    protected $fileRepository;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Fal\FalService
     */
    protected $falService;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        ProcessedFileRepository $fileRepository,
        FalService $falService,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->fileRepository = $fileRepository;
        $this->falService = $falService;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * @inheritDoc
     */
    public function process(array $definition, FileInfo $fileInfo, Request $request, array $config): void
    {
        // Process the file
        $fileOrReference = $fileInfo->isFileReference() ? $fileInfo->getFileReference() : $fileInfo->getFile();
        $processed = $this->falService->getResizedImage($fileOrReference, $definition);
        if (! $processed->exists()) {
            throw new NotFoundException('File was not found on the file system');
        }
        
        // Dump the redirect file
        $realUrl = $processed->getPublicUrl(false);
        /** @noinspection BypassedUrlValidationInspection */
        if (! filter_var($realUrl, FILTER_VALIDATE_URL)) {
            $realUrl = '/' . $realUrl;
        }
        $this->defaultRedirect = $realUrl;
        
        // Handle web-p generation
        if (in_array(strtolower($processed->getExtension()), ['png', 'webp', 'jpg', 'jpeg'])) {
            try {
                $definition['hash'] = $processed->getSha1();
            } catch (InvalidHashException $exception) {
                throw new NotFoundException('File was not found on the file system');
            }
            $definition['asWebP'] = true;
            $processedWebp = $this->fileRepository->findOneByOriginalFileAndTaskTypeAndConfiguration(
                $fileInfo->getFile(), ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $definition);
            
            // Generate the new webp image and store it as processed file
            if ($processedWebp->isNew()) {
                // Set executable directories based on TYPO3 config
                $processor = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] ?? null;
                if (! empty($processor)) {
                    if ($processor === 'ImageMagick') {
                        if (! defined('WEBPCONVERT_IMAGEMAGICK_PATH')) {
                            define('WEBPCONVERT_IMAGEMAGICK_PATH',
                                ($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] ?? '') . 'convert');
                        }
                    } elseif ($processor === 'GraphicsMagick') {
                        if (! defined('WEBPCONVERT_GRAPHICSMAGICK_PATH')) {
                            define('WEBPCONVERT_GRAPHICSMAGICK_PATH',
                                ($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] ?? '') . 'gm');
                        }
                    }
                }
                
                // Generate the webp version of the file
                $tmpFile = sys_get_temp_dir() . '/' . md5($processed->getIdentifier()) . '.' . $processed->getExtension();
                Fs::writeFile($tmpFile, $processed->getContents());
                $tmpFileOut = $tmpFile . '.webp';
                WebPConvert::convert($tmpFile, $tmpFileOut, $config['webpConverterOptions'] ?? []);
                
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
            
            // Prepare webp redirect
            $realUrl = $processedWebp->getPublicUrl(false);
            /** @noinspection BypassedUrlValidationInspection */
            if (! filter_var($realUrl, FILTER_VALIDATE_URL)) {
                $realUrl = '/' . $realUrl;
            }
            
            $this->webPRedirect = $realUrl;
        }
        
        $e = $this->eventDispatcher->dispatch(new CoreImagingPostProcessorEvent(
            $definition, $fileInfo, $request, $this->defaultRedirect, $this->webPRedirect,
            $processed, $processedWebp ?? null, $config
        ));
        
        $this->defaultRedirect = $e->getDefaultRedirect();
        $this->webPRedirect = $this->getWebPRedirect();
    }
    
}
