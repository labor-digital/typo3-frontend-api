<?php
/*
 * Copyright 2022 LABOR.digital
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
 * Last modified: 2022.05.09 at 06:19
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3BetterApi\FileAndFolder\FileInfo\FileInfo;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingContext;

abstract class AbstractImagingPostProcessorEvent
{

    /**
     * The processing definition to apply to the image
     *
     * @var array
     */
    protected $definition;

    /**
     * The file information object containing the file/file reference
     *
     * @var \LaborDigital\Typo3BetterApi\FileAndFolder\FileInfo\FileInfo
     */
    protected $fileInfo;

    /**
     * The context gathered by the GET parameters of the imaging endpoint
     *
     * @var \LaborDigital\Typo3FrontendApi\Imaging\ImagingContext
     */
    protected $context;

    /**
     * The absolute path to redirect the request to when a non-web-p image is requested
     *
     * @var string
     */
    protected $defaultRedirect;

    /**
     * The absolute path to the redirect if a .webp image is requested, or null if the image can't be converted into webp
     *
     * @var string|null
     */
    protected $webPRedirect;

    public function __construct(array $definition, FileInfo $fileInfo, ImagingContext $context, string $defaultRedirect, ?string $webPRedirect)
    {
        $this->definition      = $definition;
        $this->fileInfo        = $fileInfo;
        $this->context         = $context;
        $this->defaultRedirect = $defaultRedirect;
        $this->webPRedirect    = $webPRedirect;
    }

    /**
     * Returns the processing definition to apply to the image
     *
     * @return array
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * Returns the file information object containing the file/file reference
     *
     * @return \LaborDigital\Typo3BetterApi\FileAndFolder\FileInfo\FileInfo
     */
    public function getFileInfo(): FileInfo
    {
        return $this->fileInfo;
    }

    /**
     * Returns the context gathered by the GET parameters of the imaging endpoint
     *
     * @return \LaborDigital\Typo3FrontendApi\Imaging\ImagingContext
     */
    public function getContext(): ImagingContext
    {
        return $this->context;
    }

    /**
     * Returns the absolute path to redirect the request to when a non-web-p image is requested
     *
     * @return string
     */
    public function getDefaultRedirect(): string
    {
        return $this->defaultRedirect;
    }

    /**
     * Updates the absolute path to redirect the request to when a non-web-p image is requested
     *
     * @param   string  $defaultRedirect
     *
     * @return $this
     */
    public function setDefaultRedirect(string $defaultRedirect): self
    {
        $this->defaultRedirect = $defaultRedirect;

        return $this;
    }

    /**
     * Returns the absolute path to the redirect if a .webp image is requested, or null if the image can't be converted into webp
     *
     * @return string|null
     */
    public function getWebPRedirect(): ?string
    {
        return $this->webPRedirect;
    }

    /**
     * Updates the absolute path to the redirect if a .webp image is requested, or null if the image can't be converted into webp
     *
     * @param   string|null  $webPRedirect
     *
     * @return $this
     */
    public function setWebPRedirect(?string $webPRedirect): self
    {
        $this->webPRedirect = $webPRedirect;

        return $this;
    }

}
