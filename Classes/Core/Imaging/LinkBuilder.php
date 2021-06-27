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
 * Last modified: 2021.06.24 at 12:41
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Imaging;


use GuzzleHttp\Psr7\Uri;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo;
use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3fa\Core\Link\ApiLink;
use Neunerlei\Inflection\Inflector;

class LinkBuilder implements PublicServiceInterface
{
    /**
     * Builds the imaging url for a FAL file with the provided file info
     *
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo  $fileInfo
     *
     * @return string
     * @see \LaborDigital\T3ba\Tool\Fal\FalService::getFileInfo() to get the file info
     */
    public static function build(FileInfo $fileInfo): string
    {
        if (! $fileInfo->isImage()) {
            throw new \InvalidArgumentException('The given file info does not apply to an image');
        }
        
        $identifier = $fileInfo->getFileName();
        $identifier = substr($identifier, 0, -strlen($fileInfo->getExtension()) - 1);
        $identifier = Inflector::toSlug($identifier);
        $identifier = preg_replace('~-+~', '-', $identifier);
        /** @noinspection NullPointerExceptionInspection */
        $identifier .= '.' . md5($fileInfo->getHash() . SerializerUtil::serializeJson(
                    $fileInfo->getImageInfo()->getCropVariants()
                ));
        $identifier .= '.' . ($fileInfo->isFileReference() ? 'r' : 'f') . $fileInfo->getUid();
        $identifier .= '.' . $fileInfo->getExtension();
        
        $uri = new Uri((new ApiLink())->build());
        $uri = $uri->withPath(T3FA_IMAGING_ENDPOINT_PREFIX . '/' . urlencode($identifier));
        
        return (string)$uri;
    }
}