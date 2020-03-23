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
 * Last modified: 2019.08.26 at 17:48
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Configuration;


use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouterException;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Strategy\ExtendedJsonStrategy;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigChildRepositoryInterface;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use League\Route\Route;
use League\Route\RouteGroup;
use League\Route\Router;
use League\Route\Strategy\StrategyInterface;

class RoutingConfigChildRepository implements FrontendApiConfigChildRepositoryInterface {
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
	 */
	protected $container;
	
	/**
	 * The parent repository
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $parent;
	
	/**
	 * RouterConfigRepository constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface $container
	 */
	public function __construct(TypoContainerInterface $container) {
		$this->container = $container;
	}
	
	/**
	 * Returns the uri element which defines the first part in the API uri path.
	 * @return string
	 */
	public function getRootUriPart(): string {
		return $this->parent->getConfiguration("routing")["rootUriPart"];
	}
	
	/**
	 * Returns the uri element which defines the uri part which is used to group the resource uri's under
	 * @return string
	 */
	public function getResourceBaseUriPart(): string {
		return $this->parent->getConfiguration("routing")["resourceBaseUriPart"];
	}
	
	/**
	 * Returns true if the speaking error handler should be used.
	 * False if it should never be used and NULL if the environment decides if the speaking handler is used or not.
	 * @return bool|null
	 */
	public function useSpeakingErrorHandler(): ?bool {
		return $this->parent->getConfiguration("routing")["useSpeakingErrorHandler"];
	}
	
	/**
	 * Finds the route configuration object for a given router-route.
	 *
	 * @param \League\Route\Route $route
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 * @throws \LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouterException
	 */
	public function getRouteConfig(Route $route): RouteConfig {
		foreach ($this->getRoutes() as $group) {
			foreach ($group->routes as $routeConfig) {
				if ($routeConfig->getName() === $route->getName())
					return $routeConfig;
			}
		}
		throw new ApiRouterException("I could not find the route configuration for route: {$route->getPath()}");
	}
	
	/**
	 * This method receives a new, not configured router instance and hydrates the settings with the
	 * configuration stored in this repository
	 *
	 * @param Router $router
	 * @param string $middlewareStack There are two different "stacks" of middlewares.
	 *                                - "external" is used for all external API requests using the URL.
	 *                                Here you may register additional auth middlewares to make
	 *                                sure only certain users can interact with the content.
	 *                                - "internal" is used when you access data through the
	 *                                "ResourceDataRepository" or by manually handling a request
	 *                                that sets the stack to "internal". Internal expects the request
	 *                                to be authenticated and will not cache the data.
	 */
	public function prepareRouter(Router $router, string $middlewareStack) {
		/** @var \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteGroupConfig[] $routes */
		$routes = $this->getRoutes();
		
		// Helper to map the middlewares correctly
		$middlewareFactories = $this->parent->getConfiguration("middleware")["middlewareFactories"];
		$middlewareMapper = function ($middlewareAware, array $middlewareClasses) use ($middlewareStack, $middlewareFactories) {
			if (empty($middlewareClasses)) return;
			
			/** @var \League\Route\Route|RouteGroup $middlewareAware */
			$lazyMiddlewares = $middlewares = [];
			foreach ($middlewareClasses as $middlewareClass => $middlewareConfig) {
				// Check if middleware applies to this stack
				if ($middlewareConfig["middlewareStack"] !== "both" &&
					$middlewareConfig["middlewareStack"] !== $middlewareStack) continue;
				
				// Create the middleware if required
				if (isset($middlewareFactories[$middlewareClass]))
					$middlewares[] = call_user_func($middlewareFactories[$middlewareClass]);
				else $lazyMiddlewares[] = $middlewareClass;
			}
			if (!empty($lazyMiddlewares)) $middlewareAware->lazyMiddlewares($lazyMiddlewares);
			if (!empty($middlewares)) $middlewareAware->middlewares($middlewares);
		};
		
		// Helper to create a new strategy instance
		$strategyFactory = function (string $strategyClass): StrategyInterface {
			$strategy = $this->container->get($strategyClass);
			if (method_exists($strategy, "setContainer")) $strategy->setContainer($this->container);
			return $strategy;
		};
		
		// Set default strategy
		$router->setStrategy($strategyFactory(ExtendedJsonStrategy::class));
		
		// Loop through te route configuration
		foreach ($routes as $groupConfig) {
			
			// Create a new group
			$router->group(rtrim($this->getRootUriPart() . "/" . $groupConfig->groupUri, "/"),
				function (RouteGroup $group) use ($groupConfig, $middlewareMapper, $strategyFactory) {
					
					// Loop through routes in this group
					foreach ($groupConfig->routes as $routeConfig) {
						
						// Configure a single route
						$route = $group->map($routeConfig->getMethod(), $routeConfig->getPath(), $routeConfig->getHandler());
						$route->setName($routeConfig->getName());
						if (!empty($routeConfig->getStrategy()))
							$route->setStrategy($strategyFactory($routeConfig->getStrategy()));
						$middlewareMapper($route, $routeConfig->getMiddlewares());
					}
					
					// Add the middlewares for this group
					if (!empty($groupConfig->middlewares))
						$middlewareMapper($group, $groupConfig->middlewares);
				});
		}
	}
	
	/**
	 * Returns the list of all registered routes
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteGroupConfig[]
	 */
	public function getRoutes(): array {
		return $this->parent->getConfiguration("routes");
	}
	
	/**
	 * @inheritDoc
	 */
	public function __setParentRepository(FrontendApiConfigRepository $parent): void {
		$this->parent = $parent;
	}
}