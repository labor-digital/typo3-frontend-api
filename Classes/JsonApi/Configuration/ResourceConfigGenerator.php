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
 * Last modified: 2019.08.26 at 10:55
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Configuration;


use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\CachedStackGeneratorInterface;
use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteGroupConfig;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerInterface;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Core\SingletonInterface;

class ResourceConfigGenerator implements CachedStackGeneratorInterface, SingletonInterface {
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
	 */
	protected $container;
	
	/**
	 * ResourceConfigGenerator constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface $container
	 */
	public function __construct(TypoContainerInterface $container) {
		$this->container = $container;
	}
	
	/**
	 * @inheritDoc
	 */
	public function generate(array $stack, ExtConfigContext $context, array $additionalData, $option) {
		
		// Create the config map object
		$configMap = $this->container->get(ResourceConfigMap::class);
		$routes = [];
		
		// Loop through the given stack
		foreach ($stack as $resourceType => $data) {
			
			// Create the configuration
			$config = $this->container->get(ResourceConfig::class);
			$config->resourceType = Inflector::toCamelBack($resourceType);
			$configurator = $this->container->get(ResourceConfigurator::class, ["args" => [$config]]);
			
			// Loop through the stack
			$context->runWithCachedValueDataScope($data, function (string $configClass) use ($configurator, $context) {
				// Validate the controller class
				if (!in_array(ResourceConfigurationInterface::class, class_implements($configClass)))
					throw new JsonApiException(
						"The registered resource configurator: $configClass does not implement the required interface: " .
						ResourceConfigurationInterface::class
					);
				
				// Update the configurator instance
				$controllerClass = in_array(ResourceControllerInterface::class, class_implements($configClass)) ? $configClass : NULL;
				if (!empty($controllerClass)) $configurator->setControllerClass($controllerClass);
				
				// Configure the resource
				call_user_func([$configClass, "configureResource"], $configurator, $context);
			});
			
			// Generate mappings
			$configMap->resources[$config->resourceType] = $config;
			foreach ($config->classes as $class) $configMap->classResourceMap[$class] = $config->resourceType;
			
			// Make route group
			$routeGroup = RouteGroupConfig::makeNew("/" . $additionalData["resourceBaseUriPart"] . "/" . $config->resourceType);
			$routes[] = $routeGroup;
			$routeGroup->routes = [
				$configurator->getResourceRoute(),
				$configurator->getResourceRelationRoute(),
				$configurator->getResourceRelationshipsRoute(),
				$configurator->getCollectionRoute(),
			];
			foreach ($configurator->getAdditionalRoutes() as $route) $routeGroup->routes[] = $route;
			
		}
		
		// Done
		return ["map" => $configMap, "routes" => $routes];
	}
	
}