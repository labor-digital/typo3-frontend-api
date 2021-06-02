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


namespace LaborDigital\T3fa\Api\Resource\Entity;


class PageEntity
{
    /**
     * The page id we hold the representation for
     *
     * @var int
     */
    protected $id;
    
    /**
     * The language code used to generate the site
     *
     * @var string
     */
    protected $languageCode;
    
    /**
     * The site identifier which contains this page
     *
     * @var string
     */
    protected $siteIdentifier;
    
    /**
     * The prepared resource attributes
     *
     * @var array
     */
    protected $attributes = [];
    
    public function __construct(
        int $id,
        string $languageCode,
        string $siteIdentifier,
        array $attributes
    )
    {
        $this->id = $id;
        $this->languageCode = $languageCode;
        $this->siteIdentifier = $siteIdentifier;
        $this->attributes = $attributes;
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
     * Returns the language code used to generate the site
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }
    
    /**
     * Returns the language alternatives in form of hrefLang link definitions
     *
     * @return array
     */
    public function getHrefLangUrls(): array
    {
        return $this->attributes['meta']['hrefLang'] ?? [];
    }
    
    /**
     * Returns the site identifier which contains this page
     *
     * @return string
     */
    public function getSiteIdentifier(): string
    {
        return $this->siteIdentifier;
    }
    
    /**
     * Returns the prepared resource attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Returns the list of links relevant to this page
     *
     * @return array
     */
    public function getLinks(): array
    {
        return $this->attributes['links'] ?? [];
    }
    
    /**
     * Returns the pages root line array
     *
     * @return array
     */
    public function getRootLine(): array
    {
        return $this->attributes['meta']['rootLine'] ?? [];
    }
    
    /**
     * Returns the list of generated meta tags for this page
     *
     * @return array
     */
    public function getMetaTags(): array
    {
        return $this->attributes['meta']['metaTags'] ?? [];
    }
    
}