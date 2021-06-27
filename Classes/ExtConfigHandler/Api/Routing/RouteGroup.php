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


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\ExtConfig\ExtConfigContext;

class RouteGroup implements NoDiInterface
{
    protected const ALLOWED_METHODS
        = [
            'GET',
            'HEAD',
            'POST',
            'PUT',
            'DELETE',
            'CONNECT',
            'OPTIONS',
            'TRACE',
            'PATCH',
        ];
    
    use MiddlewareConfigTrait;
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\ExtConfig\ExtConfigContext
     */
    protected $context;
    
    /**
     * The unique prefix for this group
     *
     * @var string
     */
    protected $prefix;
    
    /**
     * The list of registered middleware classes
     *
     * @var array
     */
    protected $middlewares = [];
    
    /**
     * The list of routes that were collected
     *
     * @var RouteConfig[]
     */
    protected $routes = [];
    
    public function __construct(ExtConfigContext $context, string $prefix)
    {
        $this->context = $context;
        $this->prefix = $prefix;
    }
    
    /**
     * Returns the given uri prefix for this group
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
    
    /**
     * Registers a new middleware class to the stack, which runs on ALL registered routes in this group.
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
    public function registerMiddleware(
        string $middlewareClass,
        array $options = []
    ): self
    {
        return $this->addMiddlewareToList($this->middlewares, $middlewareClass, $options);
    }
    
    /**
     * Removes a previously registered middleware from the group stack
     *
     * @param   string  $middlewareClassOrIdentifier  The middleware identifier or class name to remove
     *
     * @return $this
     */
    public function removeMiddleware(
        string $middlewareClassOrIdentifier
    ): self
    {
        return $this->removeMiddlewareFromList($this->middlewares, $middlewareClassOrIdentifier);
    }
    
    /**
     * Returns the list of all middleware configuration options for this group, by their unique identifier name
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
    
    /**
     * Checks if a middleware with the given class or identifier is registered as group middleware
     *
     * @param   string  $middlewareClassOrIdentifier  The middleware identifier or class name to check for
     *
     * @return bool
     */
    public function hasMiddleware(string $middlewareClassOrIdentifier): bool
    {
        return $this->hasMiddlewareInList($this->middlewares, $middlewareClassOrIdentifier);
    }
    
    /**
     * Can be used to amp a method for any HTTP method on a given path
     *
     * @param   string          $method   GET, POST, PUT ...
     * @param   string          $path     The route to listen to
     * @param   callable|array  $handler  The action handler, as an array of [$controllerClass, $actionMethod]
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RouteConfig
     */
    public function registerRoute(string $method, string $path, array $handler): RouteConfig
    {
        $method = strtoupper(trim($method));
        if (! in_array($method, static::ALLOWED_METHODS, true)) {
            throw new InvalidArgumentException('The given HTTP method: ' . $method . ' is invalid. Allowed are: ' . implode(', ', static::ALLOWED_METHODS));
        }
        
        if (count($handler) !== 2) {
            throw new InvalidArgumentException('A route handler must contain exactly two elements');
        }
        $id = md5($method . '.' . $path);
        
        // Make a new route
        /** @var \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RouteConfig $route */
        $route = $this->makeInstance(RouteConfig::class, [
            $method,
            $path,
            $handler,
            $this->context->getNamespace(),
        ]);
        
        $this->routes[$id] = $route;
        
        return $route;
    }
    
    /**
     * Retrieves a specific route config object
     *
     * @param   string  $method  The HTTP method to retrieve the route for
     * @param   string  $path    The uri path of the route to resolve
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RouteConfig|null
     */
    public function getRoute(string $method, string $path): ?RouteConfig
    {
        $id = md5(strtoupper(trim($method)) . '.' . $path);
        
        return $this->routes[$id] ?? null;
    }
    
    /**
     * Adds a route to this group, useful if you want to move routes between groups
     *
     * @param   \LaborDigital\T3fa\ExtConfigHandler\Api\Routing\RouteConfig  $route
     *
     * @return $this
     */
    public function addRoute(RouteConfig $route): self
    {
        $id = md5(strtoupper(trim($route->getMethod())) . '.' . $route->getPath());
        
        $this->routes[$id] = $route;
        
        return $this;
    }
    
    /**
     * Returns true if a route with the given name already exists
     *
     * @param   string|RouteConfig  $routeOrNameOrPath  The name, path or instance of the route to check for
     *
     * @return bool
     */
    public function hasRoute($routeOrNameOrPath): bool
    {
        foreach ($this->routes as $route) {
            if ($route === $routeOrNameOrPath || $route->getName() === $routeOrNameOrPath || $route->getPath() === $routeOrNameOrPath) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Removes a previously mapped route from the configuration
     *
     * @param   string|RouteConfig  $routeOrNameOrPath  The name, path or instance of the route to remove
     *
     * @return $this
     */
    public function removeRoute($routeOrNameOrPath): self
    {
        $cleanList = [];
        foreach ($this->routes as $route) {
            if ($route === $routeOrNameOrPath || $route->getName() === $routeOrNameOrPath || $route->getPath() === $routeOrNameOrPath) {
                continue;
            }
            
            $cleanList[] = $route;
        }
        
        $this->routes = $cleanList;
        
        
        return $this;
    }
    
    /**
     * Returns all routes that have been registered in this group
     *
     * @return RouteConfig[]
     */
    public function getAllRoutes(): array
    {
        return array_values($this->routes);
    }
    
    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param   string          $path
     * @param   callable|array  $handler
     *
     * @return RouteConfig
     */
    public function get(string $path, array $handler): RouteConfig
    {
        return $this->registerRoute('GET', $path, $handler);
    }
    
    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param   string          $path
     * @param   callable|array  $handler
     *
     * @return RouteConfig
     * @see registerRoute() for parameter descriptions
     */
    public function post(string $path, array $handler): RouteConfig
    {
        return $this->registerRoute('POST', $path, $handler);
    }
    
    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param   string          $path
     * @param   callable|array  $handler
     *
     * @return RouteConfig
     */
    public function put($path, array $handler): RouteConfig
    {
        return $this->registerRoute('PUT', $path, $handler);
    }
    
    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param   string          $path
     * @param   callable|array  $handler
     *
     * @return RouteConfig
     */
    public function patch(string $path, array $handler): RouteConfig
    {
        return $this->registerRoute('PATCH', $path, $handler);
    }
    
    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param   string          $path
     * @param   callable|array  $handler
     *
     * @return RouteConfig
     */
    public function delete(string $path, array $handler): RouteConfig
    {
        return $this->registerRoute('DELETE', $path, $handler);
    }
    
    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param   string          $path
     * @param   callable|array  $handler
     *
     * @return RouteConfig
     */
    public function head(string $path, array $handler): RouteConfig
    {
        return $this->registerRoute('HEAD', $path, $handler);
    }
    
    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param   string          $path
     * @param   callable|array  $handler
     *
     * @return RouteConfig
     */
    public function options(string $path, array $handler): RouteConfig
    {
        return $this->registerRoute('OPTIONS', $path, $handler);
    }
}