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
 * Last modified: 2021.06.24 at 18:17
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Event\Resource\Page;


use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class PageDataModelFilterEvent
 *
 * Emitted when the page resource information is generated.
 * Allows you to modify the ext base domain model used to generate the page fields with
 *
 * @package LaborDigital\T3fa\Event\Resource\Page
 */
class PageDataModelFilterEvent
{
    
    /**
     * The domain model used to generate the data attributes for the page
     *
     * @var \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
     */
    protected $model;
    
    /**
     * The page data object that gets currently filled (NOTE: not all fields have content yet!)
     *
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData
     */
    protected $pageData;
    
    public function __construct(AbstractEntity $model, PageData $pageData)
    {
        $this->model = $model;
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
     * Returns the hydrated domain model used to generate the data attributes for the page
     *
     * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
     */
    public function getModel(): AbstractEntity
    {
        return $this->model;
    }
    
    /**
     * Allows you to exchange the domain model used to generate the data attributes for the page
     *
     * @param   \TYPO3\CMS\Extbase\DomainObject\AbstractEntity  $model
     */
    public function setModel(AbstractEntity $model): void
    {
        $this->model = $model;
    }
}