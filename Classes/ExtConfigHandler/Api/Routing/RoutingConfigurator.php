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
 * Last modified: 2021.06.02 at 20:35
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api\Routing;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Configuration\State\ConfigState;
use Neunerlei\Configuration\Util\IntuitiveTopSorter;

class RoutingConfigurator extends AbstractExtConfigConfigurator
{
    
    use ContainerAwareTrait;
    use MiddlewareConfigTrait;
    
    /**
     * The list of all existing route groups by their prefix
     *
     * @var \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RouteGroup[]
     */
    protected $groups = [];
    
    /**
     * The list of middleware classes that will be executed for every registered route
     *
     * @var array
     */
    protected $globalMiddlewares = [];
    
    /**
     * Registers a new middleware class to the stack, which runs on ALL registered routes.
     *
     * @param   string  $middlewareClass       The class to register. This class MUST implement the Middleware
     *                                         interface
     * @param   array   $options               Additional options for this middleware
     *                                         - identifier string: By default the middleware identifier is calculated
     *                                         based on the class name. If you set this you can overwrite the default.
     *                                         - before array|string: A list of or a single, middleware identifier to
     *                                         place this middleware in front of
     *                                         - after array|string: A list of or a single, middleware identifier to
     *                                         place this middleware after
     *
     * @return $this
     * @see \Psr\Http\Server\MiddlewareInterface
     */
    public function registerGlobalMiddleware(
        string $middlewareClass,
        array $options = []
    ): self
    {
        return $this->addMiddlewareToList($this->globalMiddlewares, $middlewareClass, $options);
    }
    
    /**
     * Removes a previously registered middleware from the global stack
     *
     * @param   string  $middlewareClassOrIdentifier  The middleware identifier or class name to remove
     *
     * @return $this
     */
    public function removeGlobalMiddleware(
        string $middlewareClassOrIdentifier
    ): self
    {
        return $this->removeMiddlewareFromList($this->globalMiddlewares, $middlewareClassOrIdentifier);
    }
    
    /**
     * Returns the list of all global middleware configuration options, by their unique identifier name
     *
     * @return array
     */
    public function getGlobalMiddlewares(): array
    {
        return $this->globalMiddlewares;
    }
    
    /**
     * Checks if a middleware with the given class or identifier is registered as global middleware
     *
     * @param   string  $middlewareClassOrIdentifier  The middleware identifier or class name to check for
     *
     * @return bool
     */
    public function hasGlobalMiddleware(string $middlewareClassOrIdentifier): bool
    {
        return $this->hasMiddlewareInList($this->globalMiddlewares, $middlewareClassOrIdentifier);
    }
    
    /**
     * Similar to getRouteGroup() but returns a new route group instance if it currently is not registered
     *
     * @param   string|null  $prefix  The uri prefix to retrieve a specific route group, or null to retrieve the default group
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RouteGroup
     */
    public function routes(?string $prefix = null): RouteGroup
    {
        if ($prefix === null) {
            $prefix = '/';
        }
        
        $group = $this->getRouteGroup($prefix);
        if ($group !== null) {
            return $group;
        }
        
        $group = $this->makeInstance(RouteGroup::class, [$this->context, $prefix]);
        
        $this->groups[$prefix] = $group;
        
        return $group;
    }
    
    /**
     * Returns a specific route group if it exists or null if not
     * Use / to access all global routes
     *
     * @param   string  $prefix  The uri prefix for the group to retrieve
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RouteGroup|null
     */
    public function getRouteGroup(string $prefix): ?RouteGroup
    {
        if (isset($this->groups[$prefix])) {
            return $this->groups[$prefix];
        }
        
        return null;
    }
    
    /**
     * Removes a previously registered group from the list of registered routing groups
     *
     * @param   string|RouteGroup  $prefixOrGroup  Either the uri prefix or the instance of a route group to remove
     *
     * @return $this
     */
    public function removeRouteGroup($prefixOrGroup): self
    {
        $filtered = [];
        foreach ($this->groups as $prefix => $group) {
            if ($prefix === $prefixOrGroup || $group === $prefixOrGroup) {
                continue;
            }
            
            $filtered[$prefix] = $group;
        }
        
        $this->groups = $filtered;
        
        return $this;
    }
    
    /**
     * Moves all routes that have been registered for $oldPrefix into a group with $newPrefix
     * If there already is a group with $newPrefix, the routes will be added to it.
     *
     * @param   string  $oldPrefix  The uri prefix of the group to change
     * @param   string  $newPrefix  The uri prefix where the routes of the group should be added to.
     *
     * @return $this
     */
    public function changeRoutePrefix(string $oldPrefix, string $newPrefix): self
    {
        $group = $this->getRouteGroup($oldPrefix);
        
        if ($group === null) {
            return $this;
        }
        
        $newGroup = $this->routes($newPrefix);
        
        foreach ($group->getAllRoutes() as $route) {
            $newGroup->addRoute($route);
        }
        
        foreach ($group->getMiddlewares() as $identifier => $options) {
            $newGroup->registerMiddleware($options['target'], [
                'identifier' => $identifier,
                'before' => $options['before'],
                'after' => $options['after'],
            ]);
        }
        
        $this->removeRouteGroup($group);
        
        return $this;
    }
    
    /**
     * Compiles all registered routes into a list and stores them in the config state object provided
     *
     * @param   \Neunerlei\Configuration\State\ConfigState  $state
     */
    public function finish(ConfigState $state): void
    {
        $globalMiddlewares = $this->globalMiddlewares;
        
        $routes = [];
        foreach ($this->groups as $group) {
            $groupMiddlewares = $group->getMiddlewares();
            $groupPrefix = rtrim(trim($group->getPrefix()), '/') . '/';
            
            foreach ($group->getAllRoutes() as $route) {
                $config = $route->asArray();
                $config['path'] = rtrim($groupPrefix . ltrim(trim($route->getPath()), '/'), '/');
                $config['middlewares'] = $this->processMiddlewares(
                    $globalMiddlewares, $groupMiddlewares, $route->getMiddlewares());
                $routes[] = $config;
            }
        }
        
        $state->set('routes', $routes);
    }
    
    /**
     * Sorts and merges the middlewares for a single route into a single stack
     *
     * @param   array  $global  The global middleware definitions
     * @param   array  $group   The group middleware definitions
     * @param   array  $route   The route middleware definitions
     *
     * @return array
     */
    protected function processMiddlewares(array $global, array $group, array $route): array
    {
        $list = array_merge($global, $group, $route);
        
        // Sort the middlewares
        $targetIdentifierMap = Arrays::getList($list, ['target'], true);
        $sorter = new IntuitiveTopSorter(array_keys($targetIdentifierMap));
        foreach ($list as $identifier => $config) {
            foreach (['before', 'after'] as $listKey) {
                foreach ($config[$listKey] as $target) {
                    // Resolve all "before"/"after" definitions into identifiers
                    if (in_array($target, $targetIdentifierMap, true)) {
                        $pivotIdentifier = array_search($target, $targetIdentifierMap, true);
                    } elseif (isset($targetIdentifierMap[$target])) {
                        $pivotIdentifier = $target;
                    } else {
                        continue;
                    }
                    
                    if ($listKey === 'before') {
                        $sorter->moveItemBefore($identifier, $pivotIdentifier);
                    } else {
                        $sorter->moveItemAfter($identifier, $pivotIdentifier);
                    }
                }
            }
        }
        
        $middlewares = [];
        foreach ($sorter->sort() as $identifier) {
            $middlewares[] = $targetIdentifierMap[$identifier];
        }
        
        return $middlewares;
    }
}