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
 * Last modified: 2021.06.24 at 18:08
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


use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Class PageRootLineFilterEvent
 * Emitted when the Page object build's its root line.
 * It is used to filter the raw data that was retrieved from TYPO3 for the current page
 *
 * @package LaborDigital\T3fa\Event\Resource\Page
 */
class PageRootLineFilterEvent
{
    
    /**
     * The page id that currently tries to resolve it's root line
     *
     * @var int
     */
    protected $pid;
    
    /**
     * The language uid that is used to generate the root line
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    protected $language;
    
    /**
     * The raw root line array TYPO3 responded for this page
     *
     * @var array
     */
    protected $rootLine;
    
    public function __construct(int $pid, SiteLanguage $language, array $rootLine)
    {
        $this->pid = $pid;
        $this->language = $language;
        $this->rootLine = $rootLine;
    }
    
    /**
     * Returns the page id that currently tries to resolve it's root line
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }
    
    /**
     * Returns the language uid that is used to generate the root line
     *
     * @return SiteLanguage
     */
    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }
    
    
    /**
     * Returns the raw root line array TYPO3 responded for this page
     *
     * @return array
     */
    public function getRootLine(): array
    {
        return $this->rootLine;
    }
    
    /**
     * Updates the raw root line array TYPO3 responded for this page
     *
     * @param   array  $rootLine
     *
     * @return PageRootLineFilterEvent
     */
    public function setRootLine(array $rootLine): PageRootLineFilterEvent
    {
        $this->rootLine = $rootLine;
        
        return $this;
    }
    
    
}
