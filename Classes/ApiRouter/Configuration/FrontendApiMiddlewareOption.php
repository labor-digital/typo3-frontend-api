<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.01.17 at 16:50
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Configuration;


use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractChildExtConfigOption;
use Neunerlei\Arrays\Arrays;

class FrontendApiMiddlewareOption extends AbstractChildExtConfigOption {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiOption
	 */
	protected $parent;
	
	/**
	 * Holds the raw configuration while we collect the options
	 * @var array
	 */
	protected $config = [
		"middlewareFactories" => [],
		"globalMiddlewares"   => [],
		"localMiddlewares"    => [],
	];
	
	/**
	 * Returns the list of all registered, global middleware classes
	 * @return array
	 */
	public function getGlobalMiddlewares(): array {
		return array_keys($this->config["globalMiddlewares"]);
	}
	
	/**
	 * Registers a new middleware for all registered routes including the grouped routes.
	 *
	 * @param string $middlewareClass The class to use as middleware. It has to implement the Middleware interface
	 * @param array  $options         Additional configuration for your middleware
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
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiMiddlewareOption
	 * @see \Psr\Http\Server\MiddlewareInterface
	 */
	public function registerGlobalMiddleware(string $middlewareClass, array $options = []): FrontendApiMiddlewareOption {
		$this->config["globalMiddlewares"][$middlewareClass] = $options;
		return $this;
	}
	
	/**
	 * Removes a given middleware class from the global namespace
	 *
	 * @param string $middlewareClass
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiMiddlewareOption
	 * @see \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiOption::registerGlobalMiddleware()
	 */
	public function removeGlobalMiddleware(string $middlewareClass): FrontendApiMiddlewareOption {
		unset($this->config["globalMiddlewares"][$middlewareClass]);
		return $this;
	}
	
	/**
	 * Returns the list of all registered, local middleware classes for the current group
	 * @return array
	 */
	public function getLocalMiddlewares(): array {
		return array_keys(Arrays::getPath($this->config["localMiddlewares"], [$this->getGroupUriPart()], []));
	}
	
	/**
	 * Registers a middleware for the current group. If not in a group the middlewares
	 * will only be added for the root group an no other groups will be affected by them.
	 * The options are the same as for registerGlobalMiddleware();
	 *
	 * @param string $middlewareClass
	 * @param array  $options
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiMiddlewareOption
	 * @see \Psr\Http\Server\MiddlewareInterface
	 */
	public function registerLocalMiddleware(string $middlewareClass, array $options = []): FrontendApiMiddlewareOption {
		$this->config["localMiddlewares"][$this->getGroupUriPart()][$middlewareClass] = $options;
		return $this;
	}
	
	/**
	 * Removes a given middleware class from the local namespace
	 *
	 * @param string $middlewareClass
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiMiddlewareOption
	 * @see \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiOption::registerLocalMiddleware()
	 */
	public function removeLocalMiddleware(string $middlewareClass): FrontendApiMiddlewareOption {
		Arrays::removePath($this->config["localMiddlewares"], [$this->getGroupUriPart(), $middlewareClass]);
		return $this;
	}
	
	/**
	 * Some middlewares require some configuration to passed into their constructor when the instance is created.
	 * For those middlewares you can provide a factory that should return the instance of a given middleware class
	 * that is used in the middleware stack.
	 *
	 * @param string   $middlewareClass The name of the middleware class you want to register the factory for.
	 *                                  You should use the real class name here and no interface!
	 * @param callable $factory         The factory callable that is used to create the middleware instance
	 *                                  It has to return an object implementing the middleware interface
	 *
	 * @return $this
	 * @see \Psr\Http\Server\MiddlewareInterface
	 */
	public function registerMiddlewareFactory(string $middlewareClass, callable $factory): FrontendApiMiddlewareOption {
		$this->config["middlewareFactories"][$middlewareClass] = $factory;
		return $this;
	}
	
	/**
	 * Removes a previously registered middleware factory again.
	 *
	 * @param string $middlewareClass
	 *
	 * @return $this
	 * @see \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiOption::registerMiddlewareFactory()
	 */
	public function removeMiddlewareFactory(string $middlewareClass): FrontendApiMiddlewareOption {
		unset($this->config["middlewareFactories"][$middlewareClass]);
		return $this;
	}
	
	/**
	 * Internal helper to get the group uri part from the routing option
	 * @return string
	 */
	protected function getGroupUriPart(): string {
		return $this->parent->routing()->getGroupUriPart();
	}
	
	/**
	 * Internal helper to fill the main config repository' config array with the local configuration
	 *
	 * @param array $config
	 */
	public function __buildConfig(array &$config): void {
		$config["middleware"] = $this->config;
	}
}