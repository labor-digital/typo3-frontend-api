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
 * Last modified: 2020.04.01 at 12:00
 */

namespace LaborDigital\Typo3FrontendApi\Imaging;


class ImagingContext
{

    /**
     * The type of the file we should resolve via it's uid Either: "reference" or "file"
     *
     * @var string
     */
    protected $type;

    /**
     * The uid of the file or file reference we should serve
     *
     * @var int
     */
    protected $uid;

    /**
     * The unique hash for the image and variants
     *
     * @var string
     */
    protected $redirectHashPath;

    /**
     * The path to the file where the redirect information is stored
     *
     * @var string
     */
    protected $redirectPath;

    /**
     * The definition key for the resizing of the image
     *
     * @var string
     */
    protected $definition;

    /**
     * Optional crop variant to crop the image to while resizing it
     *
     * @var string|null
     */
    protected $crop;

    /**
     * True if a x2 version for retina displays is requested
     *
     * @var bool
     */
    protected $isX2;

    /**
     * ImagingContext constructor.
     *
     * @param   string       $type
     * @param   int          $uid
     * @param   string       $redirectPath
     * @param   string       $definition
     * @param   string|null  $crop
     * @param   bool         $isX2
     */
    public function __construct(
        string $type,
        int $uid,
        string $redirectPath,
        string $redirectHashPath,
        string $definition,
        ?string $crop,
        bool $isX2
    ) {
        $this->type             = $type;
        $this->uid              = $uid;
        $this->redirectPath     = $redirectPath;
        $this->redirectHashPath = $redirectHashPath;
        $this->definition       = $definition;
        $this->crop             = $crop;
        $this->isX2             = $isX2;
    }

    /**
     * Returns true if the file should be looked up as file reference
     *
     * @return bool
     */
    public function isReference(): bool
    {
        return $this->type === "reference";
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
        return $this->type;
    }

    /**
     * Returns the uid of the file or file reference we should serve
     *
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Returns the path to the file where the redirect information is stored
     *
     * @return string
     */
    public function getRedirectPath(): string
    {
        return $this->redirectPath;
    }

    /**
     * @return string
     */
    public function getRedirectHashPath(): string
    {
        return $this->redirectHashPath;
    }

    /**
     * Returns the definition key for the resizing of the image
     *
     * @return string
     */
    public function getDefinitionKey(): string
    {
        return $this->definition;
    }

    /**
     * Returns the optional crop variant to crop the image to while resizing it
     *
     * @return string|null
     */
    public function getCrop(): ?string
    {
        return $this->crop;
    }

    /**
     * Returns true if the x2 definition is required for retina images
     *
     * @return bool
     */
    public function isX2(): bool
    {
        return $this->isX2;
    }
}
