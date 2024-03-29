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
 * Last modified: 2021.06.25 at 20:02
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Backend;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Query\QueryDefaults;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\Pagination;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\Paginator;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;
use LaborDigital\T3fa\Core\Resource\ResourceConfigRepository;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformUtil;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;
use LaborDigital\T3fa\Core\Routing\Util\RequestRewriter;
use Psr\Http\Message\ServerRequestInterface;

class ResourceFactory implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    public static $resourceItemClass = ResourceItem::class;
    public static $resourceCollectionClass = ResourceCollection::class;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory
     */
    protected $transformerFactory;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $context;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Pagination\Paginator
     */
    protected $paginator;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\ResourceConfigRepository
     */
    protected $configRepository;
    
    /**
     * The resolved base urls by their matching site identifiers
     *
     * @var array
     */
    protected $baseUrlCache = [];
    
    /**
     * The resolved link query parameters that should be added to each resource link
     *
     * @var string|null
     */
    protected $linkParameters;
    
    public function __construct(
        TransformerFactory $transformerFactory,
        TypoContext $context,
        Paginator $paginator,
        ResourceConfigRepository $configRepository
    )
    {
        $this->transformerFactory = $transformerFactory;
        $this->context = $context;
        $this->paginator = $paginator;
        $this->configRepository = $configRepository;
    }
    
    /**
     * Creates a new resource item instance
     *
     * @param   mixed        $raw               The raw data that should be passed into the item
     * @param   string|null  $resourceType      The unique resource type name for the item to create.
     *                                          If this is NULL or not provided, the type is automatically resolved
     * @param   array|null   $meta              Optional metadata that should be stored for this item
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceItem
     */
    public function makeResourceItem($raw, ?string $resourceType = null, ?array $meta = null): ResourceItem
    {
        if ($raw instanceof ResourceItem) {
            return $raw;
        }
        
        if (empty($resourceType)) {
            $resourceType = $this->configRepository->getResourceType($raw) ?? $this->makeResourceType($raw);
        }
        
        return $this->makeInstance(
            static::$resourceItemClass,
            [
                $resourceType,
                $raw,
                $meta,
                $this->makeBaseUrl(),
                $this->makeLinkQueryParams(),
                $this->transformerFactory,
            ]
        );
    }
    
    /**
     * Creates a new resource collection instance
     *
     * @param   iterable         $raw           The raw data that should be passed into the collection
     * @param   string|null      $resourceType  The unique resource type name for the item to create.
     *                                          If this is NULL or not provided, the type is automatically resolved
     * @param   array|null       $meta          Optional metadata that should be stored for this item
     * @param   Pagination|null  $pagination    Optional pagination definition to be passed to the collection
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection
     */
    public function makeResourceCollection(iterable $raw, ?string $resourceType = null, ?array $meta = null, ?Pagination $pagination = null): ResourceCollection
    {
        if ($raw instanceof ResourceCollection) {
            return $raw;
        }
        
        if (empty($resourceType)) {
            $first = null;
            foreach ($raw as $item) {
                $first = $item;
                break;
            }
            
            $resourceType = $this->configRepository->getResourceType($first) ?? $this->makeResourceType($first);
        }
        
        // Create a fallback pagination object
        if ($pagination === null) {
            $config = $this->configRepository->getResourceConfig($resourceType);
            [, $pagination] = $this->paginator->paginate(
                $raw, 1, $config['pageSize'] ?? QueryDefaults::$pageSize, null
            );
        }
        
        if (! is_string($pagination->paginationLink)) {
            $pagination->paginationLink = $this->makeBaseUrl() . '/' . $resourceType . '?' . $this->makePaginationUrl();
        }
        
        return $this->makeInstance(
            static::$resourceCollectionClass,
            [
                $resourceType,
                $raw,
                $meta,
                $this->makeBaseUrl(),
                $this->makeLinkQueryParams(),
                $pagination,
                $this,
                $this->transformerFactory,
            ]
        );
    }
    
    /**
     * Helper to retrieve the correct request instance
     *
     * @return \Psr\Http\Message\ServerRequestInterface|null
     */
    public function getApiRequest(): ?ServerRequestInterface
    {
        $request = $this->context->request()->getRootRequest();
        if ($request && $request->getAttribute('originalRequest') !== null) {
            return $request->getAttribute('originalRequest');
        }
        
        return $request;
    }
    
    /**
     * Internal helper to create a dummy resource type for not configured values
     *
     * @param   $raw
     *
     * @return string
     */
    protected function makeResourceType($raw): string
    {
        $item = AutoTransformUtil::unifyValue($raw);
        
        if (is_array($item)) {
            $item = reset($item);
        } elseif (is_iterable($item)) {
            /** @noinspection LoopWhichDoesNotLoopInspection */
            foreach ($item as $v) {
                $item = $v;
                break;
            }
        }
        
        $type = get_debug_type($item);
        
        $parts = array_filter(explode('\\', $type));
        $parts = array_map('ucfirst', array_filter(['auto', array_shift($parts), array_pop($parts)]));
        
        return lcfirst(implode($parts));
    }
    
    /**
     * Builds the base url for all api links
     *
     * @return string
     */
    protected function makeBaseUrl(): string
    {
        $siteIdentifier = $this->context->site()->getCurrent()->getIdentifier();
        if (isset($this->baseUrlCache[$siteIdentifier])) {
            return $this->baseUrlCache[$siteIdentifier];
        }
        
        $host = $this->context->config()->getSiteBasedConfigValue('t3fa.site.apiHost');
        
        if (! is_string($host)) {
            $request = $this->getApiRequest();
            if (! $request) {
                return $this->baseUrlCache[$siteIdentifier] = '';
            }
            
            $uri = $request->getUri();
            $host = $uri->getScheme() . '://' . $uri->getHost();
        }
        
        $baseUri = $this->context->config()->getConfigValue('t3fa.routing.apiPath');
        
        return $this->baseUrlCache[$siteIdentifier] = $host . '/' . trim($baseUri, '/') . '/resources';
    }
    
    /**
     * Builds the pagination url of all current query parameters
     *
     * @return string
     */
    protected function makePaginationUrl(): string
    {
        $request = $this->getApiRequest();
        if (! $request) {
            return '';
        }
        
        $params = $request->getQueryParams();
        $params['page']['number'] = '___pageNumber___';
        
        return urldecode(http_build_query($params));
    }
    
    /**
     * Builds the list of query parameters that should be added to each link
     * the Fractal Serializer generates. This is important, so that the links point
     * to the same context than the request.
     *
     * @return string
     */
    protected function makeLinkQueryParams(): string
    {
        $request = $this->getApiRequest();
        if (! $request) {
            return '';
        }
        
        $linkParams = [];
        $params = $request->getQueryParams();
        foreach (
            [
                RequestRewriter::REQUEST_SLUG_QUERY_KEY,
                RequestRewriter::REQUEST_SITE_QUERY_KEY,
                RequestRewriter::REQUEST_LANG_QUERY_KEY,
            ] as $inheritedParam
        ) {
            if (isset($params[$inheritedParam])) {
                $linkParams[$inheritedParam] = $params[$inheritedParam];
            }
        }
        
        if (empty($linkParams)) {
            return '';
        }
        
        return '?' . http_build_query($linkParams);
        
    }
}