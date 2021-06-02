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
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.08.26 at 13:51
 */

namespace LaborDigital\T3fa\ExtConfigHandler\Api\Routing;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Cache\CacheOptionsTrait;
use Neunerlei\Inflection\Inflector;

class RouteConfig implements NoDiInterface
{
    use MiddlewareConfigTrait;
    use CacheOptionsTrait;
    
    /**
     * The ext config namespace used to create this route
     *
     * @var string
     */
    protected $namespace;
    
    /**
     * Defines the HTTP Method to handle with this route
     *
     * @var string
     */
    protected $method = 'GET';
    
    /**
     * The route path to handle
     *
     * @var string
     */
    protected $path;
    
    /**
     * The handler/controller that is used to process the request
     *
     * @var array
     */
    protected $handler;
    
    /**
     * The list of registered middlewares to use for this route
     *
     * @var array
     */
    protected $middlewares = [];
    
    /**
     * The unique name of this route
     *
     * @var string
     */
    protected $name;
    
    /**
     * Additional arguments that are transferred as overhead.
     *
     * @var array
     */
    protected $attributes = [];
    
    public function __construct(string $method, string $path, array $handler, string $namespace)
    {
        $this->namespace = $namespace;
        $this->name = $this->makeValidName(
            implode(
                '-',
                [
                    Inflector::toDashed($namespace),
                    Inflector::toDashed(strtolower($method)),
                    $path,
                ]
            )
        );
        
        $this->path = $path;
        $this->method = $method;
        $this->setHandler(reset($handler), end($handler));
    }
    
    /**
     * Returns the HTTP Method to handle with this route
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
    
    /**
     * Returns the route path to handle
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * Returns the handler/controller that is used to process the request
     *
     * @return array
     */
    public function getHandler(): array
    {
        return $this->handler;
    }
    
    /**
     * Sets the handler/controller that is used to process the request
     * Note: Closures are not allowed as this object should be serializable!
     *
     * @param   string  $controllerClass
     * @param   string  $actionMethod
     *
     * @return RouteConfig
     */
    public function setHandler(string $controllerClass, string $actionMethod): RouteConfig
    {
        if (! class_exists($controllerClass) || ! method_exists($controllerClass, $actionMethod)) {
            throw new \InvalidArgumentException("The given action method: $actionMethod on class: $controllerClass is not callable!");
        }
        $this->handler = [$controllerClass, $actionMethod];
        
        return $this;
    }
    
    /**
     * Can be used to add a single middleware to the stack of this specific route.
     *
     * @param   string  $middlewareClass  The class that implements the middleware interface
     * @param   array   $options          Options to define the position of this middleware in the stack
     *                                    - identifier string: By default the middleware identifier is calculated
     *                                    based on the class name. If you set this you can overwrite the default.
     *                                    - before array|string: A list of or a single, middleware identifier to
     *                                    place this middleware in front of
     *                                    - after array|string: A list of or a single, middleware identifier to
     *                                    place this middleware after
     *
     * @return $this
     */
    public function registerMiddleware(string $middlewareClass, array $options = []): self
    {
        return $this->addMiddlewareToList($this->middlewares, $middlewareClass, $options);
    }
    
    /**
     * Removes a previously registered middleware from the route
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
     * Returns the list of all middleware configuration options for this route, by their unique identifier name
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
    
    /**
     * Checks if a middleware with the given class or identifier is registered as route middleware
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
     * Returns the unique name of this route
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Sets the unique name of this route
     *
     * @param   string  $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        if ($name !== $this->makeValidName($name)) {
            throw new \InvalidArgumentException(
                'The given route name "' . $name .
                '" does not match the required pattern: [a-zA-Z0-9\-_]. This would be valid: "' .
                $this->makeValidName($name) . '"');
        }
        
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * Returns all registered attributes of this route
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Sets the registered attributes for this route
     *
     * @param   array  $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        
        return $this;
    }
    
    /**
     * Sets a single attribute to the given value
     *
     * @param   string  $key    The attribute key to set
     * @param   mixed   $value  The value to set for the given key
     *
     * @return $this
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        
        return $this;
    }
    
    /**
     * Internal helper to convert the given $name into a valid variant
     *
     * @param   string  $name
     *
     * @return string
     */
    protected function makeValidName(string $name): string
    {
        return preg_replace("~[^a-zA-Z0-9\-_]~", 'X-', $name);
    }
    
    /**
     * Returns the current configuration as array
     *
     * @return array
     */
    public function asArray(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'handler' => $this->handler,
            'middlewares' => $this->middlewares,
            'name' => $this->name,
            'attributes' => array_merge(
                $this->attributes,
                [
                    '@cacheOptions' => $this->getCacheOptions(),
                ]
            ),
        ];
    }
}
