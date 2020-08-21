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
 * Last modified: 2020.03.30 at 21:43
 */

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData;

/**
 * Class PageMetaTagsFilterEvent
 *
 * Dispatched when the PageDataTransformer generates the meta tags for the current page.
 * Can be used to filter or extend the meta tags
 *
 * @package LaborDigital\Typo3FrontendApi\Event
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
     * The object containing the information about the current page
     *
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData
     */
    protected $pageData;

    /**
     * PageMetaTagsFilterEvent constructor.
     *
     * @param   array                                                                    $tags
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData  $pageData
     */
    public function __construct(array $tags, PageData $pageData)
    {
        $this->tags     = $tags;
        $this->pageData = $pageData;
    }

    /**
     * Returns the object containing the information about the current page
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData
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


}
