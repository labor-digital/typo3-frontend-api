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
 * Last modified: 2021.06.22 at 21:35
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
use LaborDigital\T3fa\Api\Route\SchedulerController;
use LaborDigital\T3fa\Api\Route\UpController;
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
        $config = $this->context->config()->getConfigValue('t3fa.routing', []);
        $apiPath = rtrim(trim($config['apiPath'] ?? ''), '/') . '/';
        
        foreach ($this->getSiteConfig() as $routeConfig) {
            $path = $apiPath . ltrim(trim($routeConfig['path']), '/');
            $route = $router->map($routeConfig['method'], $path, $routeConfig['handler']);
            
            $route->setName($routeConfig['name']);
            
            $route->lazyPrependMiddleware(BodyParserMiddleware::class);
            $route->lazyPrependMiddleware(CacheMiddleware::class);
            
            if (! empty($routeConfig['attributes'])) {
                $route->prependMiddleware(new AttributeProviderMiddleware($routeConfig['attributes']));
            }
            
            $route->lazyMiddlewares($routeConfig['middlewares']);
        }
        
        if ($config['upRoute']) {
            $router->get($apiPath . 'up', [UpController::class, 'upAction']);
        }
        
        if (! empty($config['schedulerRoute'])) {
            $router->get($apiPath . 'scheduler/run[/{id}]', [SchedulerController::class, 'runAction'])
                   ->prependMiddleware(new AttributeProviderMiddleware($config['schedulerRoute']));
        }
    }
}