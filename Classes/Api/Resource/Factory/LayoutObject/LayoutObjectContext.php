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
 * Last modified: 2021.06.21 at 12:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\LayoutObject;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Cache\CacheOptionsTrait;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LayoutObjectContext implements NoDiInterface
{
    use CacheOptionsTrait;
    
    /**
     * The identifier of the object to generate
     *
     * @var string
     */
    protected $identifier;
    
    /**
     * The language the layout object should be generated in
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    protected $language;
    
    public function __construct(string $identifier, SiteLanguage $language)
    {
        $this->identifier = $identifier;
        $this->language = $language;
    }
    
    /**
     * Returns the identifier of the object to generate
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
    
    /**
     * Returns the language the layout object should be generated in
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }
}