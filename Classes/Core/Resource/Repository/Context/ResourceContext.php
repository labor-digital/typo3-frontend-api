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
 * Last modified: 2021.05.21 at 19:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Context;


use LaborDigital\T3ba\Tool\TypoContext\TypoContext;

class ResourceContext
{
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * The resource configuration array
     *
     * @var array
     */
    protected $config;
    
    /**
     * Additional metadata for the response object
     *
     * @var array|null
     */
    protected $meta;
    
    public function __construct(TypoContext $typoContext, array $config)
    {
        $this->typoContext = $typoContext;
        $this->config = $config;
    }
    
    /**
     * Returns the typo context instance
     *
     * @return \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    public function getTypoContext(): TypoContext
    {
        return $this->typoContext;
    }
    
    /**
     * Returns the raw resource configuration array
     *
     * @return array
     */
    public function getResourceConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Returns the options that have been provided to this resource when it was registered
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->config['options'];
    }
    
    /**
     * Returns the resource type name this context applies to
     *
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->config['type'];
    }
    
    /**
     * Returns additional metadata that is set for the data
     *
     * @return array|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }
    
    /**
     * Can be used to add additional metadata to the data
     *
     * @param   array|null  $meta
     *
     * @return $this
     */
    public function setMeta(?array $meta): self
    {
        $this->meta = $meta;
        
        return $this;
    }
    
}