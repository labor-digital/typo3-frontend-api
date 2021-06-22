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
 * Last modified: 2021.06.22 at 18:20
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement\Response;


use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Tool\Cache\Util\CacheUtil;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\ContentElement\Adapter\ViewAdapter;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class JsonResponse extends Response implements NoDiInterface
{
    
    /**
     * True when the body was set -> Meaning we will no longer dump the generated content
     *
     * @var bool
     */
    protected $bodyWasSet = false;
    
    /**
     * The raw database row of this element
     *
     * @var array
     */
    protected $row;
    
    /**
     * The namespace of that scopes the $type of the element.
     * The final Type will be $TypeNs/$Type/$SubType where $TypeNs is the camel case version of the ext key.
     * It should be used by the frontend framework to determine which component can handle this element
     *
     * @var string
     */
    protected $typeNs;
    
    /**
     * The type of that defines the element, which will be scoped by $typeNs.
     * The final Type will be $TypeNs/$Type/$SubType where $Type is the camel case version of controller name.
     * It should be used by the frontend framework to determine which component can handle this element
     *
     * @var string
     */
    protected $type;
    
    /**
     * A subtype to define multiple variants of this element.
     * The final Type will be $TypeNs/$Type/$SubType where $TypeNs is by default the name of the action.
     * It should be used by the frontend framework to determine which component can handle this element
     *
     * @var string|null
     */
    protected $subType;
    
    /**
     * The view which is used to collect the data for the element
     *
     * @var ViewInterface
     */
    protected $view;
    
    /**
     * The localized and therefore immutable data array of this element.
     * This data will have priority over the data defined in the view object.
     *
     * @var array
     */
    protected $data = [];
    
    /**
     * Each key/property in $data will be transformed independently into an array.
     * This allows us to configure transformer options for each of the entries which will be stored here.
     *
     * @var array
     */
    protected $dataTransformerOptions = [];
    
    /**
     * The initial state query containing the information to retrieve a resource collection
     * to be included in the response of this element
     *
     * @var array|null
     */
    protected $initialStateQuery;
    
    /**
     * The list of css classes that will be provided for this content element
     *
     * @var array
     */
    protected $cssClasses = [];
    
    /**
     * Contains the number of seconds for which the cache should be valid.
     * This is null if the default caching duration should be used.
     *
     * @var int|null
     */
    protected $cacheLifetime;
    
    /**
     * True if the result should be cached, false if not
     *
     * @var bool
     */
    protected $cacheEnabled = true;
    
    /**
     * The list of tags that should be associated with the cache entry
     *
     * @var array
     */
    protected $cacheTags = [];
    
    /**
     * @inheritDoc
     */
    public function __construct(
        string $typeNs,
        string $type,
        ?string $subType,
        ViewInterface $view,
        array $row,
        array $cssClasses
    )
    {
        parent::__construct();
        $this->typeNs = $typeNs;
        $this->type = $type;
        $this->subType = $subType;
        $this->view = $view;
        $this->row = $row;
        $this->cssClasses = $cssClasses;
    }
    
    /**
     * Returns the namespace of that scopes the $type of the element.
     *
     * @return string
     */
    public function getTypeNs(): string
    {
        return $this->typeNs;
    }
    
    /**
     * Updates the type of that defines the element, which will be scoped by $typeNs.
     * The final Type will be $TypeNs/$Type/$SubType where $Type is the camel case version of controller name.
     * It should be used by the frontend framework to determine which component can handle this element
     *
     * @param   string  $typeNs
     *
     * @return $this
     */
    public function withTypeNs(string $typeNs): self
    {
        $typeNs = trim($typeNs);
        
        if (empty($typeNs)) {
            throw new InvalidArgumentException('The type namespace can not be empty');
        }
        
        $clone = clone $this;
        $clone->typeNs = $typeNs;
        
        return $clone;
    }
    
    /**
     * Returns the type of that defines the element, which will be scoped by $typeNs
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Updates the type of that defines the element, which will be scoped by $typeNs.
     * The final Type will be $TypeNs/$Type/$SubType where $Type is the camel case version of controller name.
     * It should be used by the frontend framework to determine which component can handle this element
     *
     * @param   string  $type
     *
     * @return $this
     */
    public function withType(string $type): self
    {
        $type = trim($type);
        
        if (empty($type)) {
            throw new InvalidArgumentException('The type can not be empty');
        }
        
        $clone = clone $this;
        $clone->type = $type;
        
        return $clone;
    }
    
    /**
     * Returns a subtype to define multiple variants of this element
     *
     * @return string|null
     */
    public function getSubType(): ?string
    {
        return $this->subType;
    }
    
    /**
     * Updates the subtype to define multiple variants of this element.
     * e final Type will be $TypeNs/$Type/$SubType where $TypeNs is by default the name of the action.
     * should be used by the frontend framework to determine which component can handle this element
     *
     * @param   string|null  $subType
     *
     * @return $this
     */
    public function withSubType(?string $subType): self
    {
        $subType = is_string($subType) ? trim($subType) : null;
        $subType = empty($subType) ? null : $subType;
        
        $clone = clone $this;
        $clone->subType = $subType;
        
        return $clone;
    }
    
    /**
     * Returns the configured initial state query or null if there is none
     *
     * @return array|null
     */
    public function getInitialStateQuery(): ?array
    {
        return $this->initialStateQuery;
    }
    
    /**
     * The initial state query containing the information to retrieve a resource collection
     * to be included in the response of this element. This is an optional feature that allows you
     * to avoid ajax requests to the resource api endpoint when your frontend framework renders the content elements.
     *
     * @param   mixed       $resourceType  Either the name of a class, or an object that represents the resource type to find
     * @param   array|null  $query         An optional resource query (in a similar syntax as your query parameters in the url)
     *                                     {@see https://jsonapi.org/format/#fetching}
     * @param   array       $options       Additional options to apply when resolving the resource
     *                                     - pid int: Can be used to change the page id of the executed process.
     *                                     If this is left empty the current page id is used
     *                                     - language int|string|SiteLanguage: The language to set the environment to.
     *                                     Either as sys_language_uid value, as iso code or as language object
     *                                     - site string: Can be set to a valid site identifier to simulate the request
     *                                     on a specific TYPO3 site.
     *                                     - asAdmin bool (FALSE): Take a look at "asAdmin" here:
     *                                     {@link EnvironmentSimulator::runWithEnvironment()}
     *                                     - transformerOptions array: An optional array of options
     *                                     to be passed to the "asArray" method when the resource is transformed
     *                                     into an array. See this for the list of options: {@link ResourceCollection::asArray()}
     *                                     IMPORTANT: the initialState will ALWAYS be transformed to be compliant
     *                                     to the json api specifications. Meaning the "jsonApi" option is not functional here!
     *
     * @return $this
     * @see \LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository::getCollection()
     * @see \LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection::asArray()
     */
    public function withInitialStateQuery($resourceType, array $query, array $options = []): self
    {
        $clone = clone $this;
        
        $transformerOptions = $options['transformerOptions'] ?? [];
        $transformerOptions = is_array($transformerOptions) ? $transformerOptions : [];
        unset($options['transformerOptions']);
        
        $clone->initialStateQuery = [
            'resourceType' => $resourceType,
            'query' => $query,
            'options' => $options,
            'transformerOptions' => $transformerOptions,
        ];
        
        return $clone;
    }
    
    /**
     * Returns the currently available data to be passed to the frontend framework.
     * The data is inherited from the registered variables on your controller's view object.
     *
     * @param   bool  $includeViewData  By default the locally configured data as well as the data
     *                                  inside the view variables are returned. Set this to false
     *                                  to retrieve only the local data
     *
     * @return array
     */
    public function getData(bool $includeViewData = true): array
    {
        if (! $includeViewData) {
            return $this->data;
        }
        
        return array_merge(
            ViewAdapter::getVariables($this->view),
            $this->data
        );
    }
    
    /**
     * Updates the available data to the given array.
     *
     * IMPORTANT: All data will be merged with the current view variables inside the controller.
     * The data set by this method will ALWAYS have priority over variables set inside the view.
     *
     * @param   array  $data
     *
     * @return $this
     */
    public function withData(array $data): self
    {
        $clone = clone $this;
        $clone->data = $data;
        
        return $clone;
    }
    
    /**
     * Returns the currently set transformer options array for a single data key / property
     *
     * @param   string|null  $dataKey  The key/property of the value in $data or the view variable list
     *                                 If set to null all registered options will be returned
     *
     * @return array|null
     */
    public function getDataTransformerOptions(?string $dataKey): ?array
    {
        if ($dataKey === null) {
            return array_filter($this->dataTransformerOptions);
        }
        
        return $this->dataTransformerOptions[$dataKey];
    }
    
    /**
     * Each value inside the data list will be transformed independently into it's json representation.
     * This allows you to pass specific options for the transformation of each element along.
     *
     * @param   array|null   $options      Options for the transformation {@link AutoTransformer::transform()}
     * @param   string|null  $dataKey      If set you can define a "byKey" configuration option which gets
     *                                     added to the existing configuration
     *
     * @return $this
     */
    public function withDataTransformerOptions(?array $options, ?string $dataKey = null): self
    {
        $clone = clone $this;
        
        if ($dataKey === null) {
            $clone->dataTransformerOptions = $options;
        } else {
            $clone->dataTransformerOptions['byKey'][$dataKey] = $options;
        }
        
        return $clone;
    }
    
    /**
     * Returns the list of all registered css classes.
     *
     * @return array
     */
    public function getCssClasses(): array
    {
        return array_values($this->cssClasses);
    }
    
    /**
     * Each element can pass css classes to the frontend framework to implement the TYPO3 core css
     * class definitions. Additionally you can use this method to define more classes that should be passed
     * alongside the core css classes.
     *
     * @param   string|array  $classes  Either an array or a comma separated string of css classes that should be
     *                                  passed to your frontend framework
     *
     * @return $this
     */
    public function withCssClasses($classes): self
    {
        $clone = clone $this;
        $clone->cssClasses = [];
        
        return $clone->withAddedCssClasses($classes);
    }
    
    /**
     * Similar to withCssClasses() but keeps the existing css classes
     *
     * @param   string|array  $classes  Either an array or a comma separated string of css classes that should be
     *                                  passed to your frontend framework
     *
     * @return \LaborDigital\T3fa\Core\ContentElement\Response\JsonResponse
     */
    public function withAddedCssClasses($classes): self
    {
        if (is_string($classes)) {
            $classes = Arrays::makeFromStringList($classes, ' ');
        }
        
        if (! is_array($classes)) {
            throw new InvalidArgumentException('Invalid css class list given!');
        }
        
        $this->cssClasses = array_unique(array_merge($this->cssClasses, $classes));
        
        return $this;
    }
    
    /**
     * Removes one or more classes from the configured css classes.
     *
     * @param   string|array  $classes  Either an array or a comma separated string of css classes that should be
     *                                  passed to your frontend framework
     *
     * @return \LaborDigital\T3fa\Core\ContentElement\Response\JsonResponse
     */
    public function removeCssClasses($classes): self
    {
        if (is_string($classes)) {
            $classes = Arrays::makeFromStringList($classes, ' ');
        }
        
        if (! is_array($classes)) {
            throw new InvalidArgumentException('Invalid css class list given!');
        }
        
        $this->cssClasses = array_diff($this->cssClasses, $classes);
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $this->bodyWasSet = true;
        
        return parent::withBody($body);
    }
    
    /**
     * @inheritDoc
     */
    public function getBody()
    {
        if (! $this->bodyWasSet) {
            return TypoContext::getInstance()->di()->getService(BodyBuilder::class)
                              ->build($this, $this->view, $this->row);
        }
        
        return parent::getBody();
    }
    
    /**
     * Converts this response object into an html html response object
     * The data will be wrapped as data attribute on a div tag
     *
     * @param   string|null  $wrap  Allows you to override the default html wrap to be used.
     *                              You can use "|" to determine where the json body should be rendered
     *                              Alternatively use "|||" to place the json body html encoded
     *                              (for usage in data-attributes)
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function asHtml(?string $wrap = null): ResponseInterface
    {
        if (empty($wrap)) {
            $wrap = '<div data-frontend-api-content-element="|||"></div>';
        }
        
        $body = (string)$this->getBody();
        /** @noinspection NonSecureHtmlentitiesUsageInspection */
        $wrapped = str_replace(
            ['|||', '|'],
            [htmlentities($body), $body],
            $wrap
        );
        
        $response = TypoContext::getInstance()->di()->getService(ResponseFactoryInterface::class)->createResponse();
        $response = $response->withHeader('Content-Type', 'text/html');
        
        return $response->withBody(Utils::streamFor($wrapped));
    }
    
    
    /**
     * Adds a the given cache tag to all currently opened scopes
     *
     * @param   mixed  $tag  The tag to add. This can be a multitude of different types.
     *                       Check stringifyTag() for all allowed types
     *
     * @return $this
     * @see \LaborDigital\T3ba\Tool\Cache\Util\CacheUtil::stringifyTag()
     */
    public function withAddedCacheTag($tag): self
    {
        return $this->withAddedCacheTags([$tag]);
    }
    
    /**
     * Exactly the same as "addTag" but accepts multiple tags at once
     *
     * @param   array  $tags
     *
     * @return $this
     */
    public function withAddedCacheTags(array $tags): self
    {
        $tagList[] = $this->cacheTags;
        foreach ($tags as $tag) {
            $tagList[] = CacheUtil::stringifyTag($tag);
        }
        
        $clone = clone $this;
        $clone->cacheTags = array_unique(array_merge(...$tagList) ?? []);
        
        return $clone;
    }
    
    /**
     * Sets a list the cache tags for this and the parent scope
     *
     * @param   array  $tags  The list of tags to add. Check stringifyTag() for all allowed types
     *
     * @return $this
     * @see \LaborDigital\T3ba\Tool\Cache\Util\CacheUtil::stringifyTag()
     */
    public function withCacheTags(array $tags): self
    {
        $clone = clone $this;
        $clone->cacheTags = [];
        
        return $clone->withAddedCacheTags($tags);
    }
    
    /**
     * Returns the list of tags available in this scope
     *
     * @return array
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }
    
    /**
     * Sets the given lifetime to all currently open scopes.
     * The ttl acts as a max value for all open scopes. All scopes with a lower ttl will be unaffected
     *
     * WARNING: 0 means forever not disable cache!
     *
     * @param   int|null  $lifetime
     *
     * @return $this
     */
    public function withCacheLifetime(?int $lifetime): self
    {
        $this->cacheLifetime = $lifetime;
        
        return $this;
    }
    
    /**
     * Returns the minimum ttl of this and all child scopes that is currently set.
     *
     * @return int|null
     */
    public function getCacheLifetime(): ?int
    {
        return $this->cacheLifetime;
    }
    
    /**
     * Announces the cache enabled status of the current scope.
     * It only acts if the cache needs to be disabled, which means all currently opened scopes have to be disabled as
     * well
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function withCacheEnabled(bool $state): self
    {
        $this->cacheEnabled = $state;
        
        return $this;
    }
    
    /**
     * Returns true if this cache is enabled, false if not
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }
    
    /**
     * Allows you to announce all caching options collected in the CacheOptionTrait at once
     *
     * @param   array  $options  The result of CacheOptionsTrait::getCacheOptionsArray();
     * @param   bool   $addTags  By default the tags are replaced with the ones given.
     *                           If set to true, the tags are added to the existing ones instead.
     *
     * @return $this
     * @see \LaborDigital\T3fa\Core\Cache\CacheOptionsTrait
     */
    public function withCacheOptions(array $options, bool $addTags = false): self
    {
        $clone = clone $this;
        
        if (isset($options['lifetime'])) {
            $clone = $clone->withCacheLifetime($options['lifetime']);
        }
        
        if (isset($options['enabled'])) {
            $clone = $clone->withCacheEnabled($options['enabled']);
        }
        
        if (isset($options['tags'])) {
            if ($addTags) {
                $clone = $clone->withAddedCacheTags($options['tags']);
            } else {
                $clone = $clone->withCacheTags($options['tags']);
            }
        }
        
        return $clone;
    }
    
    /**
     * Returns the array of configured cache options
     *
     * @return array
     */
    public function getCacheOptions(): array
    {
        return [
            'lifetime' => $this->cacheLifetime,
            'enabled' => $this->cacheEnabled,
            'tags' => $this->cacheTags,
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string)$this->getBody();
    }
    
    
}