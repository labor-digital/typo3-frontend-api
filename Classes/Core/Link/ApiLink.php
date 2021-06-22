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
 * Last modified: 2021.06.22 at 13:02
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Link;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class ApiLink implements NoDiInterface
{
    /**
     * Defines the path of the link that should be created.
     * This defines the path after example.org/api/...
     *
     * @var string|null
     */
    protected $path;
    
    /**
     * Allows you to create the $path using reverse routing.
     * Holds the name of the route you want to link to. If your route contains placeholders
     * They have to be provided in the $routeArguments property
     *
     * @var string|null
     */
    protected $routeName;
    
    /**
     * Optional arguments, used to fill route placeholders when $routeName was set.
     * This is not used when $path was set instead
     *
     * @var array
     */
    protected $routeArguments = [];
    
    /**
     * Can contain the identifier of a specific site to link to
     *
     * @var string|null
     */
    protected $site;
    
    /**
     * Can contain a language object to link to
     *
     * @var SiteLanguage|null
     */
    protected $language;
    
    /**
     * The slug query parameter is used by the api to determine the page that
     * should be used as context when the request is handled. This is the slug you would
     * normally see in your browser when you access the page.
     *
     * For more dynamic generation you can use the $slugLinkBuilder instead
     *
     * @var string|null
     */
    protected $slug;
    
    /**
     * Alternatively to the static $slug property, the slug builder allows you to
     * create the slug using either a T3BA Link object or the extbase uri builder.
     *
     * @var Link|UriBuilder|null
     */
    protected $slugLinkBuilder;
    
    /**
     * Additional, optional query parameters that should be added to the build link.
     * Note: Those parameters are not taken into account when the cHash is generated.
     *
     * @var array|null
     */
    protected $queryParams;
    
    /**
     * Defines the path of the link that should be created.
     * This defines the path after example.org/api/...
     *
     * @param   string|null  $path  The path should start with a /
     *
     * @return $this
     * @see withRouteName for generating the path of existing routes by their name
     */
    public function withPath(?string $path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        
        return $clone;
    }
    
    /**
     * Returns the currently configured path to use for the generated link
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }
    
    /**
     * Allows you to create the $path using reverse routing.
     * Holds the name of the route you want to link to. If your route contains placeholders
     * They have to be provided in the $routeArguments property
     *
     * NOTE: If $path is set, this option will be ignored.
     *
     * @param   string|null  $routeName
     *
     * @return $this
     * @see withPath for setting the path statically
     * @see withRouteArguments for setting arguments in the configured route
     */
    public function withRouteName(?string $routeName): self
    {
        $clone = clone $this;
        $clone->routeName = $routeName;
        
        return $clone;
    }
    
    /**
     * Returns the configured route name to use as reverse routing option.
     *
     * @return string|null
     */
    public function getRouteName(): ?string
    {
        return $this->routeName;
    }
    
    /**
     * Provides optional arguments, used to fill route placeholders when $routeName was set.
     * This is not used when $path was set instead
     *
     * @param   array  $arguments  An associative array where the key is the name of the placeholder
     *                             and the value the value to be inserted into it.
     *                             Please note: The values should be url friendly! So no objects here.
     *
     * @return $this
     * @see withRouteName to set the name of the route that contains the provided placeholders
     */
    public function withRouteArguments(array $arguments): self
    {
        $clone = clone $this;
        $clone->routeArguments = $arguments;
        
        return $clone;
    }
    
    /**
     * Returns the configured route arguments
     *
     * @return array
     */
    public function getRouteArguments(): array
    {
        return $this->routeArguments;
    }
    
    /**
     * Allows you to change the site in which the request should be resolved
     *
     * @param   string|SiteInterface|null  $siteOrIdentifier  Either the identifier of the site,
     *                                                        a site instance or null to reset the value
     *
     * @return $this
     */
    public function withSite($siteOrIdentifier): self
    {
        if ($siteOrIdentifier instanceof SiteInterface) {
            $siteOrIdentifier = $siteOrIdentifier->getIdentifier();
        }
        
        if (! is_string($siteOrIdentifier) && $siteOrIdentifier !== null) {
            throw new \InvalidArgumentException('The given site is not a site object or a string');
        }
        
        $clone = clone $this;
        $clone->site = $siteOrIdentifier;
        
        return $clone;
    }
    
    /**
     * Returns the currently configured site identifier, or null if the default should be used
     *
     * @return string|null
     */
    public function getSite(): ?string
    {
        return $this->site;
    }
    
    /**
     * Is used to set the language (L parameter) of the currently configured link.
     * Note: Using this will override the L parameter in your "args"
     *
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null|int|string  $language
     *
     * @return $this
     * @throws \LaborDigital\T3ba\Tool\Link\LinkException
     */
    public function withLanguage($language): self
    {
        $clone = clone $this;
        if (! is_null($language)) {
            $context = TypoContext::getInstance();
            if (! is_object($language)) {
                foreach ($context->language()->getAllFrontendLanguages($this->site) as $lang) {
                    if (
                        (is_numeric($language) && $lang->getLanguageId() === (int)$language)
                        || strtolower($lang->getTwoLetterIsoCode()) === $language
                    ) {
                        $language = $lang;
                        break;
                    }
                }
            }
            if (! $language instanceof SiteLanguage) {
                throw new InvalidLinkException(
                    'The given language could not be found on site: '
                    . $this->site
                );
            }
        }
        $clone->language = $language;
        
        return $clone;
    }
    
    /**
     * Returns the currently configured language or null
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     */
    public function getLanguage(): ?SiteLanguage
    {
        return $this->language;
    }
    
    /**
     * The slug query parameter is used by the api to determine the page that
     * should be used as context when the request is handled. This is the slug you would
     * normally see in your browser when you access the page.
     *
     * For more dynamic generation you can use the $slugLinkBuilder instead
     *
     * @param   string|null  $slug  The relative url to a typo3 page
     *
     * @return $this
     * @see withSlugLinkBuilder to use a link builder to generate the slug instead
     */
    public function withSlug(?string $slug): self
    {
        $clone = clone $this;
        $clone->slug = is_string($slug) ? '/' . rtrim($slug, '/') : null;
        
        return $clone;
    }
    
    /**
     * Returns the configured, static slug, or null if there is none
     *
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }
    
    /**
     * Alternatively to the static $slug property, the slug builder allows you to
     * create the slug using either a T3BA Link object or the extbase uri builder.
     *
     * NOTE: If $slug was set to a static value, this configuration will be ignored.
     *
     * @param   Link|UriBuilder|null  $linkBuilder  Any kind of known link builder to generate the slug with
     *
     * @return $this
     */
    public function withSlugLinkBuilder($linkBuilder): self
    {
        $clone = clone $this;
        
        if ($linkBuilder !== null) {
            if (! $linkBuilder instanceof Link && ! $linkBuilder instanceof UriBuilder) {
                throw new \InvalidArgumentException(
                    'The given link builder is invalid. Only objects of class: '
                    . Link::class . ' or ' . UriBuilder::class . ' are supported'
                );
            }
        }
        $clone->slugLinkBuilder = $linkBuilder;
        
        return $clone;
    }
    
    /**
     * Returns the configured slug builder instance or null if there is none
     *
     * @return \LaborDigital\T3ba\Tool\Link\Link|\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder|null
     */
    public function getSlugLinkBuilder()
    {
        return $this->slugLinkBuilder;
    }
    
    /**
     * Adds additional query parameters to be added to the generated link.
     *
     * @param   array|null  $params
     *
     * @return $this
     */
    public function withQueryParams(?array $params): self
    {
        $clone = clone $this;
        $clone->queryParams = $params;
        
        return $clone;
    }
    
    /**
     * Returns the configured query parameters or null if there are none
     *
     * @return array|null
     */
    public function getQueryParams(): ?array
    {
        return $this->queryParams;
    }
    
    /**
     * Converts the link object into a string representation
     *
     * @return string
     */
    public function build(): string
    {
        return TypoContext::getInstance()->di()->getService(Dumper::class)->build($this);
    }
    
    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->build();
    }
    
}