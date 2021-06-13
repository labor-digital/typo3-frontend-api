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
 * Last modified: 2021.06.13 at 20:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing;


use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\VarFs\VarFs;
use LaborDigital\T3ba\ExtConfig\Traits\SiteConfigAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Routing\Strategy\ExtendedApplicationStrategy;
use LaborDigital\T3fa\Middleware\Api\AttributeProviderMiddleware;
use LaborDigital\T3fa\Middleware\Api\BodyParserMiddleware;
use LaborDigital\T3fa\Middleware\Api\CacheMiddleware;
use League\Route\Route;
use League\Route\Router;

class RouterFactory
{
    use SiteConfigAwareTrait;
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Core\VarFs\VarFs
     */
    protected $varFs;
    
    public function __construct(TypoContext $context, VarFs $varFs)
    {
        $this->context = $context;
        $this->varFs = $varFs;
        $this->registerConfig('t3fa.routing.routes');
    }
    
    /**
     * Returns the prepared router instance for the current site
     *
     * @return \League\Route\Router
     */
    public function getRouter(): Router
    {
        $cache = $this->varFs->getCache();
        $cacheKey = 't3fa.router.prepared.' . $this->getSiteIdentifier();
        
        if ($cache->has($cacheKey)) {
            $router = unserialize($cache->get($cacheKey), [
                'allowed_classes' => [
                    Router::class,
                    Route::class,
                    AttributeProviderMiddleware::class,
                    RouteCollector::class,
                    Std::class,
                    GroupCountBased::class,
                ],
            ]);
        }
        
        if (! isset($router) || ! $router instanceof Router) {
            $router = $this->makeInstance(Router::class);
            $this->buildRouter($router);
            
            $cache->set($cacheKey, serialize($router));
        }
        
        $strategy = $this->makeInstance(ExtendedApplicationStrategy::class);
        $strategy->setContainer($this->getContainer());
        $router->setStrategy($strategy);
        
        // @todo an event would be nice here
        
        return $router;
        
    }
    
    /**
     * Builds up the given router instance by registering all configured routes
     *
     * @param   \League\Route\Router  $router
     */
    public function buildRouter(Router $router): void
    {
        $apiPath = rtrim(trim(
                $this->context->config()->getConfigValue('t3fa.routing.apiPath', '')
            ), '/') . '/';
        
        foreach ($this->getSiteConfig() as $config) {
            $path = $apiPath . ltrim(trim($config['path']), '/');
            $route = $router->map($config['method'], $path, $config['handler']);
            
            $route->setName($config['name']);
            
            $route->lazyPrependMiddleware(BodyParserMiddleware::class);
            $route->lazyPrependMiddleware(CacheMiddleware::class);
            
            if (! empty($config['attributes'])) {
                $route->prependMiddleware(new AttributeProviderMiddleware($config['attributes']));
            }
            
            $route->lazyMiddlewares($config['middlewares']);
        }
    }
}