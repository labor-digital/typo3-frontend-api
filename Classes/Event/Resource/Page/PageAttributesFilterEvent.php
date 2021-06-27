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
 * Last modified: 2021.06.24 at 18:29
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Event\Resource\Page;


use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;

/**
 * Class PageAttributesFilterEvent
 *
 * Emitted when the page resource information is generated.
 * Allows last minute post-processing on the page data before it is cached
 *
 * @package LaborDigital\T3fa\Event\Resource\Page
 */
class PageAttributesFilterEvent
{
    
    /**
     * The fully populated page data object to be filtered
     *
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData
     */
    protected $pageData;
    
    public function __construct(PageData $pageData)
    {
        $this->pageData = $pageData;
    }
    
    /**
     * Returns the unique id of the page the data should be filtered for
     *
     * @return int
     */
    public function getUid(): int
    {
        return $this->pageData->uid;
    }
    
    /**
     * Returns the list of attributes to provide to the page resource
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->pageData->attributes;
    }
    
    /**
     * Allows you to override the attributes for the page data object
     *
     * @param   array  $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes): self
    {
        $this->pageData->attributes = $attributes;
        
        return $this;
    }
    
    /**
     * Returns the fully populated page data object to be filtered
     *
     * @return \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData
     */
    public function getPageData(): PageData
    {
        return $this->pageData;
    }
}