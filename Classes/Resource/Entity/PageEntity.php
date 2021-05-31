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
 * Last modified: 2021.05.31 at 10:50
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Resource\Entity;


use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class PageEntity
{
    /**
     * The page id we hold the representation for
     *
     * @var int
     */
    protected $id;
    
    /**
     * Holds the page's root line array after it was resolved
     *
     * @var array|null
     */
    protected $rootLine;
    
    /**
     * The language object used to generate the site
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    protected $language;
    
    /**
     * The site which contains this page
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteInterface
     */
    protected $site;
    
    /**
     * The list of links relevant to this page
     *
     * @var array
     */
    protected $links;
    
    public function __construct(int $id, SiteLanguage $language, SiteInterface $site, array $links)
    {
        $this->id = $id;
        $this->language = $language;
        $this->site = $site;
        $this->links = $links;
    }
    
    /**
     * Returns the page id this object represents
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Returns the language object used to generate the site
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }
    
    /**
     * Returns the site which contains this page
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteInterface
     */
    public function getSite(): SiteInterface
    {
        return $this->site;
    }
    
    /**
     * Returns the list of links relevant to this page
     *
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }
    
    
}