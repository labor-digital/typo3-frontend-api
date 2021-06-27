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
 * Last modified: 2021.06.24 at 18:16
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
 * Last modified: 2020.03.30 at 21:50
 */

namespace LaborDigital\T3fa\Event\Resource\Page;

use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;

/**
 * Class PageDataPageInfoFilterEvent
 *
 * Emitted when the page resource information is generated.
 * Allows you to filter the table data, where the slide-fields already have been applied.
 *
 * @package LaborDigital\T3fa\Event\Resource\Page
 */
class PageInfoFilterEvent
{
    
    /**
     * The raw page data as an array to filter
     *
     * @var array
     */
    protected $info;
    
    /**
     * The page data object that gets currently filled (NOTE: not all fields have content yet!)
     *
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData
     */
    protected $pageData;
    
    public function __construct(array $row, PageData $pageData)
    {
        $this->info = $row;
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
     * Returns the raw page data as an array to filter
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }
    
    /**
     * Updates the raw page data as an array to filter
     *
     * @param   array  $info
     *
     * @return PageInfoFilterEvent
     */
    public function setInfo(array $info): PageInfoFilterEvent
    {
        $this->info = $info;
        
        return $this;
    }
    
    
}
