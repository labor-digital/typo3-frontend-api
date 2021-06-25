<?php /*
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
 * Last modified: 2021.06.25 at 19:17
 */
/** @noinspection SummerTimeUnsafeTimeManipulationInspection */
declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api;


use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\ExtConfigHandler\Api\LayoutObject\LayoutObjectCollector;
use LaborDigital\T3fa\ExtConfigHandler\Api\Page\PageConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RoutingConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\Api\Transformer\TransformerConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\Api\Translation\TranslationConfigurator;
use Neunerlei\Configuration\State\ConfigState;

class ApiConfigurator implements NoDiInterface
{
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\Api\Transformer\TransformerConfigurator
     */
    protected $transformerCollector;
    
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\Api\Page\PageConfigurator
     */
    protected $pageConfigurator;
    
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RoutingConfigurator
     */
    protected $routingConfigurator;
    
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\Api\LayoutObject\LayoutObjectCollector
     */
    protected $layoutObjectCollector;
    
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\Api\Translation\TranslationConfigurator
     */
    protected $translationConfigurator;
    
    /**
     * The host and schema the framework should always use when api links are generated
     *
     * @var string|null
     */
    protected $apiHost;
    
    /**
     * The number of seconds how long the cache entries should be valid by default
     *
     * @var int
     */
    protected $cacheDefaultLifetime = 60 * 60 * 24 * 365;
    
    /**
     * The number of seconds how long the CacheMiddleware should store request caches.
     * By default we limit the lifetime of the cache to a day here,
     * because the request cache handles a lot of possible permutations.
     * This allows the garbage collector to flush the cache more frequently.
     *
     * @var int
     * @see \LaborDigital\T3fa\Middleware\Api\CacheMiddleware
     */
    protected $requestCacheLifetime = 60 * 60 * 24;
    
    public function __construct(
        TransformerConfigurator $transformerCollector,
        PageConfigurator $pageConfigurator,
        RoutingConfigurator $routingConfigurator,
        LayoutObjectCollector $layoutObjectCollector,
        TranslationConfigurator $translationConfigurator
    )
    {
        $this->transformerCollector = $transformerCollector;
        $this->pageConfigurator = $pageConfigurator;
        $this->routingConfigurator = $routingConfigurator;
        $this->layoutObjectCollector = $layoutObjectCollector;
        $this->translationConfigurator = $translationConfigurator;
    }
    
    /**
     * Returns the host and schema the framework should always use when api links are generated
     * If null is returned, the url will be generated context sensitive
     *
     * @return string|null
     */
    public function getApiHost(): ?string
    {
        return $this->apiHost;
    }
    
    /**
     * Allows you to set the host and schema the framework should always use when api links are generated
     * If null is set, the url will be generated context sensitive.
     * Note: it is possible to add a path segment after the host.
     *
     * @param   string|null  $apiHost
     *
     * @return $this
     */
    public function setApiHost(?string $apiHost): self
    {
        $uri = new Uri($apiHost);
        
        $scheme = $uri->getScheme();
        $path = $uri->getPath();
        $host = $uri->getHost();
        
        if ($scheme === '') {
            $scheme = 'https';
        }
        
        if ($host === '') {
            if (str_contains($path, '.') && ! str_contains($path, '/')) {
                $host = $path;
                $path = '';
            } else {
                throw new InvalidArgumentException('The given api host is invalid. You must define a host name');
            }
        }
        
        $this->apiHost = rtrim($scheme . '://' . $host . '/' . ltrim($path, '/'), '/');
        
        return $this;
    }
    
    /**
     * Returns the default lifetime in seconds of a cache entry when nothing other was specified
     *
     * @return int
     */
    public function getCacheDefaultLifetime(): int
    {
        return $this->cacheDefaultLifetime;
    }
    
    /**
     * Allows you to update the default lifetime in seconds of a cache entry when nothing other was specified
     * DEFAULT: 60 * 60 * 24 * 365
     *
     * @param   int  $lifetime
     *
     * @return $this
     */
    public function setCacheDefaultTtl(int $lifetime): self
    {
        $this->cacheDefaultLifetime = $lifetime;
        
        return $this;
    }
    
    /**
     * Returns the number of seconds how long the CacheMiddleware should store request caches.
     *
     * @return int
     */
    public function getRequestCacheLifetime()
    {
        return $this->requestCacheLifetime;
    }
    
    /**
     * Allows you to configure the number of seconds how long the CacheMiddleware should store request caches.
     * By default we limit the lifetime of the cache to a day here,
     * because the request cache handles a lot of possible permutations.
     * This allows the garbage collector to flush the cache more frequently.
     * DEFAULT: 60 * 60 * 24
     *
     * @param   int  $lifetime
     *
     * @return $this
     * @see \LaborDigital\T3fa\Middleware\Api\CacheMiddleware
     */
    public function setRequestCacheLifetime(int $lifetime): self
    {
        $this->requestCacheLifetime = $lifetime;
        
        return $this;
    }
    
    /**
     * Access to the list of globally registered transformers for this site
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Transformer\TransformerConfigurator
     */
    public function transformer(): TransformerConfigurator
    {
        return $this->transformerCollector;
    }
    
    /**
     * Access to the list of page related resource options
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Page\PageConfigurator
     */
    public function page(): PageConfigurator
    {
        return $this->pageConfigurator;
    }
    
    /**
     * Access to the list of routing options and route configuration
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RoutingConfigurator
     */
    public function routing(): RoutingConfigurator
    {
        return $this->routingConfigurator;
    }
    
    /**
     * Access to the list of layout objects to be provided for this site.
     * Layout objects are meant for static parts of your page layout, like menus, breadcrumbs or login forms.
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\LayoutObject\LayoutObjectCollector
     */
    public function layoutObjects(): LayoutObjectCollector
    {
        return $this->layoutObjectCollector;
    }
    
    /**
     * Access to the translation provider, available under the /api/resources/translation endpoint
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Translation\TranslationConfigurator
     */
    public function translation(): TranslationConfigurator
    {
        return $this->translationConfigurator;
    }
    
    /**
     * Persists the local site configuration into the given state object
     *
     * @param   \Neunerlei\Configuration\State\ConfigState  $state
     */
    public function finish(ConfigState $state): void
    {
        $state->set('apiHost', $this->apiHost);
        $state->set('cache.defaultLifetime', $this->cacheDefaultLifetime);
        $state->set('cache.requestLifetime', $this->requestCacheLifetime);
    }
}