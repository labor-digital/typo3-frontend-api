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
 * Last modified: 2020.01.17 at 16:40
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Configuration;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigException;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractChildExtConfigOption;
use LaborDigital\Typo3FrontendApi\ApiRouter\Controller\RouteControllerInterface;
use LaborDigital\Typo3FrontendApi\FrontendApiException;
use Neunerlei\PathUtil\Path;

class FrontendApiRoutingOption extends AbstractChildExtConfigOption {
	
	/**
	 * The base url can be seen as a "namespace" of a part of your api.
	 * It will always follow the $rootUrl like /$rootUrl/$baseUrl/$endpointUrl
	 * In a real world example that would be /api/V1/myEndpoint
	 *
	 * @var string
	 */
	protected $groupUriPart = "";
	
	/**
	 * Holds the raw configuration while we collect the options
	 * @var array
	 */
	protected $config = [
		"rootUriPart"             => "api",
		"resourceBaseUriPart"     => "resources",
		"useSpeakingErrorHandler" => NULL,
		"strategies"              => [
			"" => "json",
		],
	];
	
	/**
	 * Returns true if the API should use the speaking error handler
	 * If this returns null, the environment will decide
	 * @return bool|null
	 */
	public function useSpeakingErrorHandler(): ?bool {
		return $this->config["useSpeakingErrorHandler"];
	}
	
	/**
	 * Sets if the speaking error handler is used
	 * If this is null, the speaking error handler is used in a dev environment and the simple handler in all other
	 * environments
	 *
	 * @param bool|null $speakingErrorHandler
	 *
	 * @return FrontendApiRoutingOption
	 */
	public function setSpeakingErrorHandler(?bool $speakingErrorHandler): FrontendApiRoutingOption {
		$this->config["useSpeakingErrorHandler"] = $speakingErrorHandler;
		return $this;
	}
	
	/**
	 * Sets the uri element which defines the first part in the API uri path.
	 * It is the main entry point to all your API endpoints
	 *
	 * @param string $rootUrl Default /api/
	 *
	 * @return $this
	 * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
	 */
	public function setRootUriPart(string $rootUrl): FrontendApiRoutingOption {
		$rootUrl = strtolower(trim(Path::unifySlashes($rootUrl, "/"), "/"));
		if (stripos($rootUrl, "/") !== FALSE)
			throw new FrontendApiException("The root uri part should only contain a single element. Use setBaseUrl() if you want to define a more complex namespace for your API.");
		$this->config["rootUriPart"] = $rootUrl;
		return $this;
	}
	
	/**
	 * Returns the uri element which defines the first part in the API uri path.
	 * @return string
	 */
	public function getRootUriPart(): string {
		return $this->config["rootUriPart"];
	}
	
	/**
	 * Sets the uri element which defines the uri part which is used to group the resource uri's under
	 *
	 * @param string $baseUri Default /resources/
	 *
	 * @return $this
	 * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
	 */
	public function setResourceBaseUriPart(string $baseUri): FrontendApiRoutingOption {
		$baseUri = strtolower(trim(Path::unifySlashes($baseUri, "/"), "/"));
		$this->config["resourceBaseUriPart"] = $baseUri;
		return $this;
	}
	
	/**
	 * Returns the uri element which defines the uri part which is used to group the resource uri's under
	 * @return string
	 */
	public function getResourceBaseUriPart(): string {
		return $this->config["resourceBaseUriPart"];
	}
	
	/**
	 * Defines the strategy to use for the router or a certain group.
	 *
	 * @param string $strategy
	 *
	 * @return $this
	 * @see https://route.thephpleague.com/4.x/strategies/
	 */
	public function setLocalStrategy(string $strategy): FrontendApiRoutingOption {
		$this->config["strategies"][$this->groupUriPart] = $strategy;
		return $this;
	}
	
	/**
	 * Returns the list of strategies, ordered by their group uri part
	 * @return array
	 * @see https://route.thephpleague.com/4.x/strategies/
	 */
	public function getStrategies(): array {
		return $this->config["strategies"];
	}
	
	/**
	 * Can be used to encapsulate certain routes into a group
	 *
	 * @param string   $groupUri The uri part to encapsulate the group with
	 * @param callable $callable The callable to execute that should add the route definitions
	 *
	 * @return $this
	 */
	public function setWithGroupUri(string $groupUri, callable $callable): FrontendApiRoutingOption {
		$baseUrlBackup = $this->groupUriPart;
		$this->groupUriPart = Path::unifySlashes($groupUri, "/");
		call_user_func($callable, $this);
		$this->groupUriPart = $baseUrlBackup;
		return $this;
	}
	
	/**
	 * Returns the base url can be seen as a "namespace" of a part of your api.
	 * It will always follow the $rootUrl like /$rootUrl/$baseUrl/$endpointUrl
	 * In a real world example that would be /api/V1/myEndpoint
	 * @return string
	 */
	public function getGroupUriPart(): string {
		return $this->groupUriPart;
	}
	
	/**
	 * Registers a NEW route controller.
	 *
	 * A route controller defines a list of routes and their methods (post/get) they should listen on
	 * and acts as frontend controller class in one. If you want to modify existing controllers use
	 * registerRouteOverride() instead.
	 *
	 * @param string $routeControllerClass A class that extends the AbstractRouteController class
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiRoutingOption
	 * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Controller\AbstractRouteController
	 */
	public function registerRouteController(string $routeControllerClass): FrontendApiRoutingOption {
		return $this->addRegistrationToCachedStack("routes", $this->groupUriPart, $routeControllerClass);
	}
	
	/**
	 * Registers an OVERRIDE for existing routes.
	 *
	 * Overrides can be used to modify, remove or extend previously registered route controllers.
	 * The configuration is done in the same way as a new route registration, but the configurator
	 * will receive an already preconfigured route collector object.
	 *
	 * @param string $routeControllerClass A class that extends the AbstractRouteController class
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiRoutingOption
	 * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Controller\AbstractRouteController
	 */
	public function registerRouteOverride(string $routeControllerClass): FrontendApiRoutingOption {
		return $this->addOverrideToCachedStack("routes", $this->groupUriPart, $routeControllerClass);
	}
	
	/**
	 * Registers a whole directory of either new controllers, or overrides for existing controllers in a directory.
	 * It will traverse all files in the given directory and find all classes that implement the
	 * RouteControllerInterface interface.
	 *
	 * @param string $directory   The directory path to load the classes from
	 * @param bool   $asOverrides True if you want to load the classes as overrides, false if the classes define new
	 *                            routes.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiRoutingOption
	 * @see \LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractExtConfigOption
	 * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Controller\RouteControllerInterface
	 */
	public function registerRoutesInDirectory(string $directory = "EXT:{{extKey}}/Classes/Controller/Route", bool $asOverrides = FALSE): FrontendApiRoutingOption {
		return $this->addDirectoryToCachedStack("routes", $directory, function (string $class) {
			return in_array(RouteControllerInterface::class, class_implements($class));
		}, function () {
			return "/" . $this->groupUriPart;
		}, $asOverrides);
	}
	
	/**
	 * Removes a previously set route controller class.
	 *
	 * @param string $routeControllerClass The controller class to remove from the configuration
	 * @param bool   $fromOverrides        True if you want to remove the class from the overrides, false if the class
	 *                                     should be removed from the new routes.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiRoutingOption
	 */
	public function removeRoute(string $routeControllerClass, bool $fromOverrides): FrontendApiRoutingOption {
		return $this->removeFromCachedStack("routes", $this->groupUriPart, $routeControllerClass, $fromOverrides);
	}
	
	/**
	 * Internal helper to fill the main config repository' config array with the local configuration
	 *
	 * @param array $config
	 *
	 * @throws \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigException
	 */
	public function __buildConfig(array &$config): void {
		
		// Extract the resource routes
		if (!is_array($config["resourceRoutes"]))
			throw new ExtConfigException("Invalid config object given, the required \"resourceRoutes\" key is missing!");
		$resourceRoutes = $config["resourceRoutes"];
		unset($config["resourceRoutes"]);
		
		// Check the middleware definition
		if (!is_array($config["middleware"]))
			throw new ExtConfigException("Invalid config object given, the required \"middleware\" key is missing!");
		
		// Build the routes
		$config["routes"] = $this->runCachedStackGenerator("routes", RouteConfigGenerator::class, [
			"strategies"        => $this->config["strategies"],
			"localMiddlewares"  => $config["middleware"]["localMiddlewares"],
			"globalMiddlewares" => $config["middleware"]["globalMiddlewares"],
			"resourceRoutes"    => $resourceRoutes,
		]);
		
		// Remove middleware definition
		unset($config["middleware"]["localMiddlewares"]);
		unset($config["middleware"]["globalMiddlewares"]);
		
		// Inject the additional routing data
		$config["routing"] = $this->config;
	}
	
}