<?php
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
 * Last modified: 2020.04.01 at 20:09
 */

namespace LaborDigital\Typo3FrontendApi\Imaging\Provider;


use LaborDigital\Typo3BetterApi\FileAndFolder\FileInfo\FileInfo;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingContext;

interface ImagingProviderInterface
{

    /**
     * Called when the redirects for the given file should be generated.
     * The method should prepare the instance so that getDefaultRedirect() and getWebPRedirect()
     * return the correct values
     *
     * @param   array           $definition  The processing definition to apply to the image
     * @param   FileInfo        $fileInfo    The file information object containing the file/file reference
     * @param   ImagingContext  $context     The context gathered by the GET parameters of the imaging endpoint
     */
    public function process(array $definition, FileInfo $fileInfo, ImagingContext $context): void;

    /**
     * Should return the absolute path to redirect the request to when a non-web-p image is requested
     * If this function returns a string starting with a slash (/) the host is automatically prepended
     *
     * @return string
     */
    public function getDefaultRedirect(): string;

    /**
     * Should return the absolute path to the redirect if a .webp image is requested.
     * Can be null if this image is not available as .webp format
     * If this function returns a string starting with a slash (/) the host is automatically prepended
     *
     * @return string|null
     */
    public function getWebPRedirect(): ?string;
}
