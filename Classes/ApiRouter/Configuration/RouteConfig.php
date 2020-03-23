<?php
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

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Configuration;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouterException;
use Neunerlei\Inflection\Inflector;

class RouteConfig {
	
	/**
	 * Defines the HTTP Method to handle with this route
	 * @var string
	 */
	protected $method = "GET";
	
	/**
	 * The route path to handle
	 * @var string
	 */
	protected $path;
	
	/**
	 * Determines if this route should use the cache or not.
	 * Note that only GET routes are cached!
	 * @var bool
	 */
	protected $useCache = TRUE;
	
	/**
	 * The handler/controller that is used to process the request
	 * @var array
	 */
	protected $handler;
	
	/**
	 * The list of registered middlewares to use for this route
	 * @var array
	 */
	protected $middlewares = [];
	
	/**
	 * Defines which strategy class is used by the php league route library.
	 * If this is empty. The default strategy will be used
	 * @var string|null
	 */
	protected $strategy;
	
	/**
	 * The unique name of this route
	 * @var string
	 */
	protected $name;
	
	/**
	 * Additional arguments that are transferred as overhead.
	 * @var array
	 */
	protected $attributes = [];
	
	/**
	 * Returns the HTTP Method to handle with this route
	 * @return string
	 */
	public function getMethod(): string {
		return $this->method;
	}
	
	/**
	 * Sets the HTTP Method to handle with this route
	 *
	 * @param string $method GET, POST...
	 *
	 * @return RouteConfig
	 */
	public function setMethod(string $method): RouteConfig {
		$this->method = strtoupper(trim($method));
		return $this;
	}
	
	/**
	 * Returns the route path to handle
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}
	
	/**
	 * Sets the route path to handle
	 *
	 * @param string $path
	 *
	 * @return RouteConfig
	 * @see https://route.thephpleague.com/4.x/routes/
	 */
	public function setPath(string $path): RouteConfig {
		$this->path = $path;
		return $this;
	}
	
	/**
	 * Returns true if this route should use the cache, false if not
	 * @return bool
	 */
	public function isUseCache(): bool {
		if ($this->method !== "GET") return FALSE;
		return $this->useCache;
	}
	
	/**
	 * Sets if this route should use the cache or not.
	 *
	 * @param bool $useCache
	 *
	 * @return RouteConfig
	 */
	public function setUseCache(bool $useCache): RouteConfig {
		$this->useCache = $useCache;
		return $this;
	}
	
	/**
	 * Returns the handler/controller that is used to process the request
	 * @return array
	 */
	public function getHandler(): array {
		return $this->handler;
	}
	
	/**
	 * Sets the handler/controller that is used to process the request
	 * Note: Closures are not allowed as this object should be serializable!
	 *
	 * @param string $handlerClass
	 * @param string $handlerMethod
	 *
	 * @return RouteConfig
	 * @throws \LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouterException
	 */
	public function setHandler(string $handlerClass, string $handlerMethod): RouteConfig {
		if (!class_exists($handlerClass) || !method_exists($handlerClass, $handlerMethod))
			throw new ApiRouterException("The given action method: $handlerMethod on class: $handlerClass is not callable!");
		$this->handler = [$handlerClass, $handlerMethod];
		return $this;
	}
	
	/**
	 * Returns the list of registered middlewares to use for this route
	 * @return array
	 */
	public function getMiddlewares(): array {
		return $this->middlewares;
	}
	
	/**
	 * Sets the list of registered middlewares to use for this route
	 *
	 * @param array  $middlewares     The list of middleware classes to set
	 * @param string $middlewareStack Defines to which stack of middlewares to add this middleware to.
	 *                                By default it will be added to both stacks.
	 *
	 * @return RouteConfig
	 */
	public function setMiddlewares(array $middlewares, string $middlewareStack = "both"): RouteConfig {
		// Convert in before / after stack
		$converted = [];
		$def = ["before" => [], "after" => [], "middlewareStack" => $middlewareStack];
		foreach ($middlewares as $k => $middleware) {
			if (is_numeric($k)) $converted[$middleware] = $def;
			else if (!is_array($middleware)) $converted[$k] = $def;
			else $converted[$k] = array_merge($def, $middleware);
		}
		$this->middlewares = $converted;
		return $this;
	}
	
	/**
	 * Can be used to add a single middleware to the stack of this specific route.
	 *
	 * @param string $middlewareClass The class that implements the middleware interface
	 * @param array  $options         Options to define the position of this middleware in the stack
	 *                                - before string|array: Either a single or multiple class names of
	 *                                other middlewares this new one should be executed in front of.
	 *                                - after string|array: Either a single or multiple class names of
	 *                                other middlewares that should be executed before this one.
	 *                                - middlewareStack string (both|external|internal): Defines to which stack of
	 *                                middlewares to add this middleware to. By default it will be added to both stacks.
	 *                                -- "external" is used for all external API requests using the URL.
	 *                                Here you may register additional auth middlewares to make
	 *                                sure only certain users can interact with the content.
	 *                                -- "internal" is used when you access data through the
	 *                                "ResourceDataRepository" or by manually handling a request
	 *                                that sets the stack to "internal". Internal expects the request
	 *                                to be authenticated and will not cache the data.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 */
	public function addMiddleware(string $middlewareClass, array $options = []): RouteConfig {
		$this->middlewares[$middlewareClass] = $options;
		return $this;
	}
	
	/**
	 * Returns the strategy class that is used by the php league route library
	 * @return string|null
	 */
	public function getStrategy(): ?string {
		return $this->strategy;
	}
	
	/**
	 * Sets the strategy class that is used by the php league route library
	 *
	 * @param string|null $strategy
	 *
	 * @return RouteConfig
	 */
	public function setStrategy(?string $strategy): RouteConfig {
		$this->strategy = $strategy;
		return $this;
	}
	
	/**
	 * Returns the unique name of this route
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}
	
	/**
	 * Sets the unique name of this route
	 *
	 * @param string $name
	 *
	 * @return RouteConfig
	 */
	public function setName(string $name): RouteConfig {
		$this->name = Inflector::toDashed(preg_replace("~[^a-zA-Z0-9\-_]~", "X-", $name));
		return $this;
	}
	
	/**
	 * Returns all registered attributes of this route
	 * @return array
	 */
	public function getAttributes(): array {
		return $this->attributes;
	}
	
	/**
	 * Sets the registered attributes for this route
	 *
	 * @param array $attributes
	 *
	 * @return RouteConfig
	 */
	public function setAttributes(array $attributes): RouteConfig {
		$this->attributes = $attributes;
		return $this;
	}
	
	/**
	 * Sets a single attribute to the given value
	 *
	 * @param string $key   The attribute key to set
	 * @param mixed  $value The value to set for the given key
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 */
	public function setAttribute(string $key, $value): RouteConfig {
		$this->attributes[$key] = $value;
		return $this;
	}
	
	
	/**
	 * Factory method to create a new route instance
	 *
	 * @param string $method        GET, POST...
	 * @param string $path          The path of the route to handle
	 * @param string $handlerClass  The controller class name
	 * @param string $handlerMethod The controller method name to call
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig|mixed
	 */
	public static function makeNew(string $method, string $path, string $handlerClass, string $handlerMethod) {
		$self = TypoContainer::getInstance()->get(static::class);
		$self->setMethod(strtoupper(trim($method)));
		$self->setName("route-" . strtolower($method) . "-" . $path);
		$self->setHandler($handlerClass, $handlerMethod);
		$self->setPath($path);
		return $self;
	}
}