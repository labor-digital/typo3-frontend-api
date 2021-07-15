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
 * Last modified: 2021.07.15 at 14:08
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Event\Resource\Page;


use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;

class PageIsPreviewFilterEvent
{
    /**
     * True if the page should be marked as "preview", false if not
     *
     * @var bool
     */
    protected $isPreview;
    
    /**
     * The page data object that gets currently filled (NOTE: not all fields have content yet!)
     *
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData
     */
    protected $pageData;
    
    public function __construct(bool $isPreview, PageData $pageData)
    {
        $this->isPreview = $isPreview;
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
     * Returns the page data object that gets currently filled (NOTE: not all fields have content yet!)
     *
     * @return \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData
     */
    public function getPageData(): PageData
    {
        return $this->pageData;
    }
    
    /**
     * Returns true if the page should be marked as "preview", false if not
     *
     * @return bool
     */
    public function isPreview(): bool
    {
        return $this->isPreview;
    }
    
    /**
     * Allows you to change the value of the preview state of the generated page
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function setIsPreview(bool $state): self
    {
        $this->isPreview = $state;
        
        return $this;
    }
}