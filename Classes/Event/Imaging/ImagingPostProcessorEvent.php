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
 * Last modified: 2021.06.25 at 18:26
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
 * Last modified: 2020.04.02 at 00:00
 */

namespace LaborDigital\T3fa\Event\Imaging;


use LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo;
use LaborDigital\T3fa\Core\Imaging\Request;

/**
 * Class ImagingPostProcessorEvent
 *
 * Emitted when the imaging processor service generated a new processed file definition and right before the redirect files are dumped.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ImagingPostProcessorEvent
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
     * @var FileInfo
     */
    protected $fileInfo;
    
    /**
     * The request object that is currently handled
     *
     * @var \LaborDigital\T3fa\Core\Imaging\Request
     */
    protected $request;
    
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
    
    /**
     * ImagingPostProcessorEvent constructor.
     *
     * @param   array                                          $definition
     * @param   \LaborDigital\T3ba\Tool\Fal\FileInfo\FileInfo  $fileInfo
     * @param   \LaborDigital\T3fa\Core\Imaging\Request        $request
     * @param   string                                         $defaultRedirect
     * @param   string|null                                    $webPRedirect
     */
    public function __construct(
        array $definition,
        FileInfo $fileInfo,
        Request $request,
        string $defaultRedirect,
        ?string $webPRedirect
    )
    {
        $this->definition = $definition;
        $this->fileInfo = $fileInfo;
        $this->request = $request;
        $this->defaultRedirect = $defaultRedirect;
        $this->webPRedirect = $webPRedirect;
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
     * @return FileInfo
     */
    public function getFileInfo(): FileInfo
    {
        return $this->fileInfo;
    }
    
    /**
     * Returns the request object that gets handled
     *
     * @return \LaborDigital\T3fa\Core\Imaging\Request
     */
    public function getRequest(): Request
    {
        return $this->request;
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
     * @return ImagingPostProcessorEvent
     */
    public function setDefaultRedirect(string $defaultRedirect): ImagingPostProcessorEvent
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
     * @return ImagingPostProcessorEvent
     */
    public function setWebPRedirect(?string $webPRedirect): ImagingPostProcessorEvent
    {
        $this->webPRedirect = $webPRedirect;
        
        return $this;
    }
    
}
