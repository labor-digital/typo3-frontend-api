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
 * Last modified: 2021.06.25 at 18:36
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Imaging;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Core\Locking\LockerTrait;
use LaborDigital\T3ba\Tool\Fal\FalService;
use LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo;
use LaborDigital\T3ba\Tool\Fal\FileInfo\ImageFileInfo;
use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Imaging\Processor\CoreImagingProcessor;
use LaborDigital\T3fa\Core\Imaging\Processor\ImagingProcessorInterface;
use LaborDigital\T3fa\Event\Imaging\ImagingPostProcessorEvent;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\FileSystem\Fs;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Error\Http\InternalServerErrorException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;

class RequestHandler implements PublicServiceInterface
{
    use ContainerAwareTrait;
    use LockerTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Fal\FalService
     */
    protected $falService;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        TypoContext $typoContext,
        FalService $falService,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->typoContext = $typoContext;
        $this->falService = $falService;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Processes the given imaging context by creating the required processed files and their matching redirect configuration
     *
     * @param   \LaborDigital\T3fa\Core\Imaging\Request  $request
     *
     * @throws \League\Route\Http\Exception\BadRequestException
     * @throws \TYPO3\CMS\Core\Error\Http\InternalServerErrorException
     */
    public function process(Request $request): void
    {
        $config = $this->typoContext->config()->getConfigValue('t3fa.imaging', []);
        
        if (! ($config['enabled'] ?? null)) {
            throw new BadRequestException('The imaging endpoint is disabled');
        }
        
        try {
            $this->acquireLock(implode(',', get_object_vars($request)));
            
            $definition = $this->resolveDefinition($config, $request);
            $fileInfo = $this->resolveFileInfo($request);
            /** @var \LaborDigital\T3ba\Tool\Fal\FileInfo\ImageFileInfo $imageInfo */
            $imageInfo = $fileInfo->getImageInfo();
            
            if (! $this->validateHash($request, $fileInfo, $imageInfo)) {
                return;
            }
            $this->resolveCropVariant($definition, $request, $imageInfo);
            
            $processor = $this->getServiceOrInstance($config['imagingProcessor'] ?? CoreImagingProcessor::class);
            if (! $processor instanceof ImagingProcessorInterface) {
                throw new InternalServerErrorException('The provider does not implement the required interface!');
            }
            
            $processor->process($definition, $fileInfo, $request, $config);
            
            $e = $this->eventDispatcher->dispatch(new ImagingPostProcessorEvent(
                $definition, $fileInfo, $request, $processor->getDefaultRedirect(), $processor->getWebPRedirect()
            ));
            
            Fs::writeFile($request->redirectInfoPath, $e->getDefaultRedirect());
            $webPRedirect = $e->getWebPRedirect();
            if (! empty($webPRedirect)) {
                Fs::writeFile($request->redirectInfoPath . '-webp', $webPRedirect);
            }
        } finally {
            $this->releaseAllLocks();
        }
    }
    
    /**
     * Resolves the image processing definition based on the given request
     *
     * @param   array                                    $config
     * @param   \LaborDigital\T3fa\Core\Imaging\Request  $request
     *
     * @return array
     */
    protected function resolveDefinition(array $config, Request $request): array
    {
        $definitions = $config['definitions'] ?? [];
        
        if (! isset($definitions['default'])) {
            $definitions['default'] = [];
        }
        
        if (! isset($definitions[$request->definition])) {
            throw new BadRequestException('Invalid definition key: "' . $request->definition . '" given');
        }
        $definition = $definitions[$request->definition];
        
        // Prepare x2 definition
        if ($request->isX2) {
            foreach ($definition as $k => $v) {
                if (empty($v) ||
                    ! in_array($k, ['width', 'height', 'maxWidth', 'maxHeight', 'minWidth', 'minHeight'], true)) {
                    continue;
                }
                
                $definition[$k] = preg_replace_callback('~[0-9.,]+~si', function ($m) {
                    $v = (float)str_replace(',', '.', $m[0]);
                    
                    return ($v * 2) . '';
                }, $v);
            }
        }
        
        return $definition;
    }
    
    /**
     * Resolves the file information for the required fal file and returns it.
     * It also validates if the file is actually an image
     *
     * @param   \LaborDigital\T3fa\Core\Imaging\Request  $request
     *
     * @return \LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo
     */
    protected function resolveFileInfo(Request $request): FileInfo
    {
        $fileOrReference = null;
        try {
            $fileOrReference = $request->isReference()
                ? $this->falService->getFileReference($request->uid)
                : $this->falService->getFile($request->uid);
        } catch (ResourceDoesNotExistException $e) {
        }
        
        if (empty($fileOrReference)) {
            throw new NotFoundException('File was not found in FAL');
        }
        
        $fileInfo = $this->falService->getFileInfo($fileOrReference);
        if (! $fileInfo->isImage()) {
            throw new NotFoundException('The requested file is not an image');
        }
        
        return $fileInfo;
    }
    
    /**
     * Checks if the file hash matches the hash we calculate based on the crop variants
     *
     * @param   \LaborDigital\T3fa\Core\Imaging\Request             $request
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo       $fileInfo
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\ImageFileInfo  $imageInfo
     *
     * @return bool
     */
    protected function validateHash(Request $request, FileInfo $fileInfo, ImageFileInfo $imageInfo): bool
    {
        if (! Fs::exists($request->redirectHashPath)) {
            $givenHash = basename($request->redirectHashPath);
            $realHash = md5($fileInfo->getHash() .
                            SerializerUtil::serializeJson($imageInfo->getCropVariants()));
            
            // If the hashes do match -> a new crop was created or the image changed -> flush the directory
            /** @noinspection HashTimingAttacksInspection */
            if ($givenHash === $realHash) {
                $dirName = dirname($request->redirectHashPath);
                Fs::flushDirectory($dirName);
                Fs::mkdir($dirName);
                Fs::touch($request->redirectHashPath);
                
                return true;
            }
            
            // The hashes don't match, does the redirect file exist?
            // Yes -> Okay handle the file like normally -> outdated link...
            if (Fs::exists($request->redirectInfoPath)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Resolves the crop variant based on the given parameter in the request
     *
     * @param   array                                               $definition
     * @param   \LaborDigital\T3fa\Core\Imaging\Request             $request
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\ImageFileInfo  $imageInfo
     */
    protected function resolveCropVariant(array &$definition, Request $request, ImageFileInfo $imageInfo): void
    {
        $cropVariants = $imageInfo->getCropVariants();
        if (isset($definition['crop'], $cropVariants[$definition['crop']])) {
            $crop = $definition['crop'];
        } else {
            $crop = isset($cropVariants['default']) ? 'default' : null;
        }
        
        if (! empty($request->crop)) {
            $givenCrop = $request->crop;
            if ($givenCrop === 'none') {
                $crop = false;
            } elseif (isset($cropVariants[$givenCrop])) {
                $crop = $givenCrop;
            }
        }
        
        if ($crop !== null) {
            $definition['crop'] = $crop;
        }
    }
}