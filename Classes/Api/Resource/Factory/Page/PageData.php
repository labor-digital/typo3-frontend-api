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
 * Last modified: 2021.06.02 at 20:22
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Page;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class PageData implements NoDiInterface
{
    /**
     * The unique id of this page
     *
     * @var int
     */
    public $pid;
    
    /**
     * The language the data is generated for
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public $language;
    
    /**
     * The site the data was generated for
     *
     * @var \TYPO3\CMS\Core\Site\Entity\Site
     */
    public $site;
    
    /**
     * The lifetime in seconds of this page
     *
     * @var int|null
     */
    public $cacheLifetime;
    
    /**
     * True if this page is configured as a redirect
     *
     * @var bool
     */
    public $isRedirect = false;
    
    /**
     * The raw info array with all slide fields already applied
     *
     * @var array
     */
    public $pageInfoArray;
    
    /**
     * The raw root line without the data providers applied to it
     *
     * @var array
     */
    public $rootLine;
    
    /**
     * The resource attributes to return in the resource
     *
     * @var array
     */
    public $attributes = [];
    
    /**
     * The map of fields and their parent pids to map references correctly
     *
     * @var array
     */
    public $slideFieldPidMap = [];
    
    /**
     * The list of all parent page info objects for slided fields to create the slided model with
     *
     * @var array
     */
    public $slideParentPageInfoMap = [];
    
    public function __construct(int $pid, SiteLanguage $language, SiteInterface $site)
    {
        $this->pid = $pid;
        $this->language = $language;
        $this->site = $site;
    }
    
    public function getConstructorArgs(): array
    {
        return array_values(
            [
                'pid' => $this->pid,
                'language' => $this->language->getTwoLetterIsoCode(),
                'site' => $this->site->getIdentifier(),
                'attributes' => $this->attributes,
            ]
        );
    }
}