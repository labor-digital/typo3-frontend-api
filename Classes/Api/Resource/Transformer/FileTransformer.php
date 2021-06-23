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
 * Last modified: 2021.06.23 at 10:28
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Transformer;


use LaborDigital\T3ba\Tool\Fal\FalService;
use LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo;
use LaborDigital\T3ba\Tool\Fal\FileInfo\ImageFileInfo;
use LaborDigital\T3ba\Tool\Fal\FileInfo\VideoFileInfo;
use LaborDigital\T3fa\Core\Resource\Transformer\AbstractResourceTransformer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\Resource\File;

class FileTransformer extends AbstractResourceTransformer implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * Defines how file types are mapped to their string representation
     */
    protected const FILE_TYPE_MAP
        = [
            File::FILETYPE_UNKNOWN => 'unknown',
            File::FILETYPE_TEXT => 'text',
            File::FILETYPE_IMAGE => 'image',
            File::FILETYPE_AUDIO => 'audio',
            File::FILETYPE_VIDEO => 'video',
            File::FILETYPE_APPLICATION => 'application',
        ];
    
    /**
     * @var \LaborDigital\T3ba\Tool\Fal\FalService
     */
    protected $falService;
    
    public function __construct(FalService $falService)
    {
        $this->falService = $falService;
    }
    
    /**
     * @inheritDoc
     */
    public function transform($value): array
    {
        if (empty($value)) {
            return ['id' => null];
        }
        
        try {
            $fileInfo = $this->falService->getFileInfo($value);
            
            // Generate the type string by the numeric value
            $type = 'unknown';
            if (isset(static::FILE_TYPE_MAP[$fileInfo->getType()])) {
                $type = static::FILE_TYPE_MAP[$fileInfo->getType()];
            }
            
            // Build information array
            $info = [
                'id' => $fileInfo->getUid(),
                'isReference' => $fileInfo->isFileReference(),
                'type' => $type,
                'filename' => $fileInfo->getFileName(),
                'mime' => $fileInfo->getMimeType(),
                'size' => $fileInfo->getSize(),
                'extension' => $fileInfo->getExtension(),
                // @todo the host should be changeable -> For using a proxy
                'url' => $fileInfo->getUrl(),
            ];
            
            if ($fileInfo->isVideo()) {
                $video = $this->transformVideo($fileInfo->getVideoInfo());
                if ($video !== null) {
                    $info['video'] = $video;
                }
            } elseif ($fileInfo->isImage()) {
                $image = $this->transformImage($fileInfo->getImageInfo(), $fileInfo);
                if ($image !== null) {
                    $info['image'] = $image;
                }
            }
            
            return $info;
            
        } catch (Throwable $e) {
            $message = 'Failed to transform file';
            
            if (isset($fileInfo)) {
                $message .= ' uid: ' . $fileInfo->getUid() . ' reference: ' . $fileInfo->isFileReference() .
                            ' name: ' . $fileInfo->getFileName();
            }
            
            $this->logger->error($message, ['exception' => $e]);
            
            // Make sure that a missing file reference does not crash the entire page
            return ['id' => null, 'error' => 'Failed to gather the file information!'];
        }
    }
    
    /**
     * Transforms the video meta information
     *
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\VideoFileInfo|null  $videoInfo
     *
     * @return array|null
     */
    protected function transformVideo(?VideoFileInfo $videoInfo): ?array
    {
        if ($videoInfo === null) {
            return null;
        }
        
        return [
            'title' => $videoInfo->getTitle(),
            'description' => $videoInfo->getDescription(),
            'autoPlay' => $videoInfo->isAutoPlay(),
            'isYouTube' => $videoInfo->isYouTube(),
            'isVimeo' => $videoInfo->isVimeo(),
            'videoId' => $videoInfo->getVideoId(),
        ];
    }
    
    /**
     * Transforms image file meta information
     *
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\ImageFileInfo|null  $imageInfo
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo            $fileInfo
     *
     * @return array|null
     */
    protected function transformImage(?ImageFileInfo $imageInfo, FileInfo $fileInfo): ?array
    {
        if ($imageInfo === null) {
            return null;
        }
        
        $image = [
            'alt' => $imageInfo->getAlt(),
            'title' => $imageInfo->getTitle(),
            'desc' => $imageInfo->getDescription(),
            'width' => $imageInfo->getWidth(),
            'height' => $imageInfo->getHeight(),
            'alignment' => $imageInfo->getImageAlignment(),
        ];
        
        
        // @todo modify this to match the imaging requirements
        if ($this->getTypoContext()->config()->getSiteBasedConfigValue('t3fa.imaging.enabled') === true) {
            $this->imagingGlue($imageInfo, $image);
        } else {
            // Build all crop variants
            $variants = [];
            $file = $fileInfo->isFileReference() ? $fileInfo->getFileReference() : $fileInfo->getFile();
            foreach ($imageInfo->getCropVariants() as $k => $conf) {
                $processed = $this->falService->getResizedImage($file, ['crop' => $k]);
                $variants[$k] = [
                    // @todo the host should be changeable -> For using a proxy
                    'url' => $this->getTypoContext()->request()->getHost() . '/' .
                             $processed->getPublicUrl(false) . '?hash=' . md5($processed->getSha1()),
                    'width' => (int)$processed->getProperty('width'),
                    'height' => (int)$processed->getProperty('height'),
                    'size' => $processed->getSize(),
                ];
            }
            
            $image['variants'] = $variants;
            
            if (empty($variants)) {
                $this->logger->error('The calculated "variants" array of an image is empty', $image);
            }
            
        }
        
        return $image;
    }
    
    /**
     * Provides the transformation glue layer to build image links for the imaging tool belt
     *
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\ImageFileInfo  $info
     * @param   array                                               $image
     */
    protected function imagingGlue(ImageFileInfo $info, array &$image): void
    {
        // @todo implement something to proxy the images from somewhere else
//        // Make the identifier
//        $cropVariants = $imageInfo->getCropVariants();
//        $identifier   = $fileInfo->getFileName();
//        $identifier   = substr($identifier, 0, -strlen($fileInfo->getExtension()) - 1);
//        $identifier   = Inflector::toSlug($identifier);
//        $identifier   = preg_replace('~-+~', '-', $identifier);
//        $identifier   .= '.' . md5($fileInfo->getHash() . \GuzzleHttp\json_encode($cropVariants));
//        $identifier   .= '.' . ($fileInfo->isFileReference() ? 'r' : 'f') . $fileInfo->getUid();
//        $identifier   .= '.' . $fileInfo->getExtension();
//
//        // Advanced endpoint
//        $endpointPath              = $configRepo->tool()->get('imaging.options.endpointDirectoryPath');
//        $endpointUrl               = Path::makeRelative($endpointPath, $this->TypoContext()->Path()->getPublicPath());
//        $info['url']               = $this->Links()->getHost() . '/' . $endpointUrl .
//                                     '/imaging.php?file=' . urlencode($identifier);
//        $info['image']['variants'] = array_keys($cropVariants);
    }
}