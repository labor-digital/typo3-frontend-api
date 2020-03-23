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
 * Last modified: 2019.08.26 at 10:38
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Configuration;


use LaborDigital\Typo3BetterApi\ExtConfig\ConfigSorter;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\CachedStackGeneratorInterface;
use LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouterException;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Strategy\ExtendedJsonStrategy;
use LaborDigital\Typo3FrontendApi\ApiRouter\Controller\RouteControllerInterface;
use LaborDigital\Typo3FrontendApi\Event\RouteGroupFilterEvent;
use League\Route\Strategy\ApplicationStrategy;
use Neunerlei\Options\Options;

class RouteConfigGenerator implements CachedStackGeneratorInterface {
	
	/**
	 * @inheritDoc
	 */
	public function generate(array $stack, ExtConfigContext $context, array $additionalData, $option) {
		
		// Prepare the global middlewares
		$globalMiddlewares = [];
		if (!empty($additionalData["globalMiddlewares"]))
			foreach ($additionalData["globalMiddlewares"] as $middlewareClass => $middlewareConfig)
				$globalMiddlewares[$middlewareClass] = $this->applyMiddlewareOptions($middlewareConfig);
		
		// Loop through all groups
		$groups = [];
		foreach ($stack as $groupBaseUri => $data) {
			
			// Create a new group
			$group = RouteGroupConfig::makeNew($groupBaseUri);
			
			// Check if we got a strategy for this group
			if (!empty($additionalData["strategies"]))
				if (isset($additionalData["strategies"][$groupBaseUri]))
					$group->strategy = $this->translateStrategyKey($additionalData["strategies"][$groupBaseUri]);
			
			// Create a new collector for this group
			$collector = $context->getInstanceOf(RouteCollector::class);
			
			// Loop through the config list
			$context->runWithCachedValueDataScope($data, function (string $configurationClass) use ($collector, $groupBaseUri) {
				// Load the controller class
				if (!in_array(RouteControllerInterface::class, class_implements($configurationClass)))
					throw new ApiRouterException(
						"The registered route controller: $configurationClass does not implement the required interface: " .
						RouteControllerInterface::class
					);
				
				// Set the controller class
				$collector->setGroupBaseUri($groupBaseUri);
				$collector->setControllerClass($configurationClass);
				
				// Collect the routes
				call_user_func([$configurationClass, "configureRoutes"], $collector);
			});
			
			// Set the collected routes in the group
			$group->routes = $collector->getRoutes();
			
			// Ignore if there are no routes
			if (empty($group->routes)) continue;
			
			// Sort the middlewares for each route
			foreach ($group->routes as $route) {
				$routeMiddlewares = $route->getMiddlewares();
				if (!empty($routeMiddlewares)) {
					foreach ($routeMiddlewares as $middlewareClass => $middlewareConfig)
						$routeMiddlewares[$middlewareClass] = $this->applyMiddlewareOptions($middlewareConfig);
					$route->setMiddlewares(ConfigSorter::sortByDependencies($routeMiddlewares));
				}
			}
			
			// Sort the middlewares for the whole group
			$middlewares = $globalMiddlewares;
			if (is_array($additionalData["localMiddlewares"][$groupBaseUri]))
				foreach ($additionalData["localMiddlewares"][$groupBaseUri] as $middlewareClass => $middlewareConfig)
					$middlewares[$middlewareClass] = $this->applyMiddlewareOptions($middlewareConfig);
			$group->middlewares = ConfigSorter::sortByDependencies($middlewares);
			
			// Store the group
			$groups[] = $group;
		}
		
		// Build the resource routes
		foreach ($additionalData["resourceRoutes"] as $group) {
			// Check if we got middlewares for this group
			$middlewares = $globalMiddlewares;
			if (!empty($group->middlewares))
				foreach ($group->middlewares as $middlewareClass => $middlewareConfig)
					$middlewares[$middlewareClass] = $this->applyMiddlewareOptions($middlewareConfig);
			$group->middlewares = ConfigSorter::sortByDependencies($middlewares);
			
			// Store the group
			$groups[] = $group;
		}
		
		// Allow filtering
		$context->EventBus->dispatch(($e = new RouteGroupFilterEvent($groups, $context)));
		$groups = $e->getGroups();
		
		// Done
		return $groups;
	}
	
	/**
	 * Internal helper to translate the simple strategy keys into their class names
	 *
	 * @param string|null $strategy
	 *
	 * @return string
	 */
	protected function translateStrategyKey(?string $strategy): string {
		if ($strategy === "json") return ExtendedJsonStrategy::class;
		else if ($strategy === "raw") return ApplicationStrategy::class;
		return $strategy;
	}
	
	/**
	 * Internal helper to apply the options definition to the options array
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	protected function applyMiddlewareOptions(array $options): array {
		return Options::make($options, [
			"before"          => [
				"default" => [],
				"type"    => ["string", "array"],
				"filter"  => function ($v) {
					return !is_array($v) ? [$v] : $v;
				},
			],
			"after"           => [
				"default" => [],
				"type"    => ["string", "array"],
				"filter"  => function ($v) {
					return !is_array($v) ? [$v] : $v;
				},
			],
			"middlewareStack" => [
				"default" => "both",
				"values"  => ["both", "internal", "external"],
			],
		]);
	}
}