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
 * Last modified: 2020.04.01 at 12:00
 */

namespace LaborDigital\Typo3FrontendApi\Imaging;


use ImagingRequest;

class ImagingContext
{

    /**
     * The imaging request object
     *
     * @var \ImagingRequest
     */
    protected $request;

    /**
     * ImagingContext constructor.
     *
     * @param   \ImagingRequest  $request
     */
    public function __construct(
        ImagingRequest $request
    ) {
        $this->request = $request;
    }

    /**
     * Returns true if the file should be looked up as file reference
     *
     * @return bool
     */
    public function isReference(): bool
    {
        return $this->request->type === 'reference';
    }

    /**
     * Returns true if the file should be looked up as a fal file
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return ! $this->isReference();
    }

    /**
     * Returns the type of the file we should resolve via it's uid Either: "reference" or "file"
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->request->type;
    }

    /**
     * Returns the uid of the file or file reference we should serve
     *
     * @return int
     */
    public function getUid(): int
    {
        return $this->request->uid;
    }

    /**
     * Returns the path to the file where the redirect information is stored
     *
     * @return string
     */
    public function getRedirectPath(): string
    {
        return $this->request->redirectInfoPath;
    }

    /**
     * @return string
     */
    public function getRedirectHashPath(): string
    {
        return $this->request->redirectHashPath;
    }

    /**
     * Returns the definition key for the resizing of the image
     *
     * @return string
     */
    public function getDefinitionKey(): string
    {
        return $this->request->definition;
    }

    /**
     * Returns the optional crop variant to crop the image to while resizing it
     *
     * @return string|null
     */
    public function getCrop(): ?string
    {
        return $this->request->crop;
    }

    /**
     * Returns true if the x2 definition is required for retina images
     *
     * @return bool
     */
    public function isX2(): bool
    {
        return $this->request->isX2;
    }

    /**
     * Returns the raw request object
     *
     * @return \ImagingRequest
     */
    public function getRequest(): ImagingRequest
    {
        return $this->request;
    }
}
