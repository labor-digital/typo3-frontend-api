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
 * Last modified: 2021.06.24 at 18:26
 */

declare(strict_types=1);
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
 * Last modified: 2021.05.03 at 12:59
 */

namespace LaborDigital\T3fa\Event\Resource\Page;


use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;

/**
 * Class PageMetaTagsFilterEvent
 *
 * Emitted when the page resource information is generated.
 * Can be used to filter or extend the meta tags
 *
 * @package LaborDigital\T3fa\Event\Resource\Page
 */
class PageMetaTagsFilterEvent
{
    
    /**
     * The meta tags for the current page
     *
     * @var array
     */
    protected $tags;
    
    /**
     * Contains the hreflang tags to hint for other language variants of this page
     *
     * @var array
     */
    protected $hrefLangUrls;
    
    /**
     * The page data object that gets currently filled (NOTE: not all fields have content yet!)
     *
     * @var PageData
     */
    protected $pageData;
    
    public function __construct(array $tags, array $hrefLangUrls, PageData $pageData)
    {
        $this->tags = $tags;
        $this->hrefLangUrls = $hrefLangUrls;
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
     * @return PageData
     */
    public function getPageData(): PageData
    {
        return $this->pageData;
    }
    
    /**
     * Returns the meta tags for the current page
     *
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }
    
    /**
     * Used to update the meta tags for the current page
     *
     * @param   array  $tags
     *
     * @return PageMetaTagsFilterEvent
     */
    public function setTags(array $tags): PageMetaTagsFilterEvent
    {
        $this->tags = $tags;
        
        return $this;
    }
    
    /**
     * Returns the hreflang tags to hint for other language variants of this page
     *
     * @return array
     */
    public function getHrefLangUrls(): array
    {
        return $this->hrefLangUrls;
    }
    
    /**
     * Sets the hreflang tags to hint for other language variants of this page
     *
     * @param   array  $hrefLangUrls
     */
    public function setHrefLangUrls(array $hrefLangUrls): void
    {
        $this->hrefLangUrls = $hrefLangUrls;
    }
    
}
