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
 * Last modified: 2019.08.19 at 11:42
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Configuration;


use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Strategy\AdditionalRouteStrategy;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Strategy\CollectionStrategy;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Strategy\ResourceStrategy;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerInterface;
use LaborDigital\Typo3FrontendApi\JsonApi\InvalidJsonApiConfigurationException;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\PostProcessing\ResourcePostProcessorInterface;
use Neunerlei\Arrays\Arrays;
use Neunerlei\PathUtil\Path;

class ResourceConfigurator {
	
	/**
	 * The resource we are currently configuring
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig
	 */
	protected $config;
	
	/**
	 * The route to view a single resource
	 * @var \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 */
	protected $resourceRoute;
	
	/**
	 * The route to show the relationships of a resource
	 * @var \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 */
	protected $resourceRelationshipsRoute;
	
	/**
	 * The route to show the related object of a single resource
	 * @var \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 */
	protected $resourceRelationRoute;
	
	/**
	 * The route to show all resources in a collection
	 * @var \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 */
	protected $collectionRoute;
	
	
	/**
	 * A list of additional routes that should be registered for this resource
	 * All routes are scoped under /api/resources/$resourceType/$additionalRoute
	 * @var array
	 */
	protected $additionalRoutes = [];
	
	/**
	 * ResourceConfigurator constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig $config
	 */
	public function __construct(ResourceConfig $config) {
		$this->config = $config;
	}
	
	/**
	 * Returns the resource type we are currently configuring
	 * @return string
	 */
	public function getResourceType(): string {
		return $this->config->resourceType;
	}
	
	/**
	 * Sets the list of properties that should be added to the output
	 * if the element was transformed into an array.
	 *
	 * Note: This affects the resulting array no matter which translator class you use.
	 *
	 * @param array $fields
	 */
	public function setAllowedProperties(array $fields) {
		$this->config->allowedProperties = $fields;
	}
	
	/**
	 * Similar to setAllowedProperties() but adds the given fields to the list of properties
	 * instead of replacing the original list.
	 *
	 * @param array $fields
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator
	 */
	public function addToAllowedProperties(array $fields): ResourceConfigurator {
		$this->config->allowedProperties = array_unique(Arrays::attach($this->config->allowedProperties, $fields));
		return $this;
	}
	
	/**
	 * Returns the list of properties that should be added to the output
	 * if the element was transformed into an array.
	 *
	 * Note: This affects the resulting array no matter which translator class you use.
	 * @return array
	 */
	public function getAllowedProperties(): array {
		return $this->config->allowedProperties;
	}
	
	/**
	 * Returns the name of the resource controller class that is used to handle this resource type.
	 * Note that an exception is thrown if there is currently no configured controller class.
	 *
	 * @return string
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function getControllerClass(): string {
		if (empty($this->config->controllerClass))
			throw new JsonApiException("The resource controller for type: {$this->config->resourceType} was requested, but was never defined!");
		return $this->config->controllerClass;
	}
	
	/**
	 * Can be used to set the controller class for this resource type.
	 * Note that the controller has to implement the resource controller interface
	 *
	 * @param string $controllerClass
	 *
	 * @return ResourceConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 * @see ResourceControllerInterface
	 */
	public function setControllerClass(?string $controllerClass): ResourceConfigurator {
		if (!empty($controllerClass) && !in_array(ResourceControllerInterface::class, class_implements($controllerClass)))
			throw new JsonApiException("The given controller class: $controllerClass is invalid, because it does not implement the required interface: " . ResourceControllerInterface::class);
		$this->config->controllerClass = $controllerClass;
		return $this;
	}
	
	
	/**
	 * Returns the instance of the route configuration that handles the resource request
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function getResourceRoute(): RouteConfig {
		if (!empty($this->resourceRoute)) return $this->resourceRoute;
		if (empty($this->config->controllerClass))
			throw new JsonApiException("The resource configuration for {$this->config->resourceType} has to define a controller class, before you can get a route instance!");
		$this->resourceRoute = RouteConfig::makeNew("GET", "/{id:number}", $this->getControllerClass(), "resourceAction");
		$this->resourceRoute->setName("json_api_resource_" . $this->config->resourceType);
		$this->resourceRoute->setAttribute("resourceType", $this->config->resourceType);
		$this->resourceRoute->setStrategy(ResourceStrategy::class);
		return $this->resourceRoute;
	}
	
	/**
	 * Returns the instance of the route configuration that handles the resource relationships request
	 *
	 * @return RouteConfig
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function getResourceRelationshipsRoute(): RouteConfig {
		if (!empty($this->resourceRelationshipsRoute)) return $this->resourceRelationshipsRoute;
		if (empty($this->config->controllerClass))
			throw new JsonApiException("The resource configuration for {$this->config->resourceType} has to define a controller class, before you can get a route instance!");
		$this->resourceRelationshipsRoute = RouteConfig::makeNew("GET", "/{id:number}/relationships/{relationship}", $this->getControllerClass(), "resourceAction");
		$this->resourceRelationshipsRoute->setName("json_api_resource_" . $this->config->resourceType . "_relationships");
		$this->resourceRelationshipsRoute->setAttribute("resourceType", $this->config->resourceType);
		$this->resourceRelationshipsRoute->setStrategy(ResourceStrategy::class);
		return $this->resourceRelationshipsRoute;
	}
	
	/**
	 * Returns the instance of the route configuration that handles a single resource relation request
	 *
	 * @return RouteConfig
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function getResourceRelationRoute(): RouteConfig {
		if (!empty($this->resourceRelationRoute)) return $this->resourceRelationRoute;
		if (empty($this->config->controllerClass))
			throw new JsonApiException("The resource configuration for {$this->config->resourceType} has to define a controller class, before you can get a route instance!");
		$this->resourceRelationRoute = RouteConfig::makeNew("GET", "/{id:number}/{related}", $this->getControllerClass(), "resourceAction");
		$this->resourceRelationRoute->setName("json_api_resource_" . $this->config->resourceType . "_relation");
		$this->resourceRelationRoute->setAttribute("resourceType", $this->config->resourceType);
		$this->resourceRelationRoute->setStrategy(ResourceStrategy::class);
		return $this->resourceRelationRoute;
	}
	
	/**
	 * Returns the instance of the route configuration that handles a resource collection
	 *
	 * @return RouteConfig
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function getCollectionRoute(): RouteConfig {
		if (!empty($this->collectionRoute)) return $this->collectionRoute;
		if (empty($this->config->controllerClass))
			throw new JsonApiException("The resource configuration for {$this->config->resourceType} has to define a controller class, before you can get a route instance!");
		$this->collectionRoute = RouteConfig::makeNew("GET", "/", $this->getControllerClass(), "collectionAction");
		$this->collectionRoute->setName("json_api_resource_" . $this->config->resourceType . "_collection");
		$this->collectionRoute->setAttribute("resourceType", $this->config->resourceType);
		$this->collectionRoute->setStrategy(CollectionStrategy::class);
		return $this->collectionRoute;
	}
	
	/**
	 * Returns the list of properties that should never show up in the output
	 * if the element was transformed into an array.
	 *
	 * @return array
	 */
	public function getDeniedProperties(): array {
		return $this->config->deniedProperties;
	}
	
	/**
	 * Sets the list of properties that should never show up in the output
	 * if the element was transformed into an array.
	 *
	 * The denied properties have higher priority than the allowed properties.
	 * Meaning a property that shows up in both arrays, will be removed from the output anyway.
	 *
	 * Note: This affects the resulting array no matter which translator class you use.
	 *
	 * @param array $deniedProperties
	 *
	 * @return ResourceConfigurator
	 */
	public function setDeniedProperties(array $deniedProperties): ResourceConfigurator {
		$this->config->deniedProperties = $deniedProperties;
		return $this;
	}
	
	/**
	 * Similar to setDeniedProperties() but adds the given properties instead of overriding the existing list
	 *
	 * @param array $properties
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator
	 */
	public function addDeniedProperties(array $properties): ResourceConfigurator {
		$this->config->deniedProperties = array_unique(Arrays::attach($this->config->deniedProperties, $properties));
		return $this;
	}
	
	/**
	 * WIP -> Don't use that yet
	 * @return bool
	 */
	public function isIncludeInternalProperties(): bool {
		return $this->config->includeInternalProperties;
	}
	
	/**
	 * WIP -> Don't use that yet
	 *
	 * @param bool $includeInternalProperties
	 *
	 * @return ResourceConfigurator
	 */
	public function setIncludeInternalProperties(bool $includeInternalProperties): ResourceConfigurator {
		$this->config->includeInternalProperties = $includeInternalProperties;
		return $this;
	}
	
	/**
	 * Returns the classes that should be associated with this resource type
	 * @return array
	 */
	public function getClasses(): array {
		return $this->config->classes;
	}
	
	/**
	 * Sets the list of classes that should be associated with this resource type
	 *
	 * @param array $classes
	 *
	 * @return ResourceConfigurator
	 */
	public function setClasses(array $classes): ResourceConfigurator {
		$this->config->classes = $classes;
		return $this;
	}
	
	/**
	 * Adds a single class to the implementations of this resource type
	 *
	 * @param string $class
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator
	 */
	public function addClass(string $class): ResourceConfigurator {
		$this->config->classes[] = $class;
		$this->config->classes = array_unique($this->config->classes);
		return $this;
	}
	
	/**
	 * Returns the registered transformer class, or null if none is configured
	 * @return string
	 */
	public function getTransformerClass() {
		return $this->config->transformerClass;
	}
	
	/**
	 * Can be used to set a class which extends the AbstractResourceTransformer class and is used
	 * as a transformer to convert an object/extbase entity into an array
	 *
	 * @param string $transformerClass
	 *
	 * @return ResourceConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function setTransformerClass(string $transformerClass) {
		if (!in_array(AbstractResourceTransformer::class, class_parents($transformerClass)))
			throw new JsonApiException(
				"The given transformer: $transformerClass does not extend the required class: " .
				AbstractResourceTransformer::class
			);
		$this->config->transformerClass = $transformerClass;
		return $this;
	}
	
	/**
	 * Returns the list of all registered transformer post processor classes
	 * @return array
	 */
	public function getTransformerPostProcessors(): array {
		return array_keys($this->config->transformerPostProcessors);
	}
	
	/**
	 * Sets the list of transformer post processor classes as an array of class names.
	 *
	 * @param array $transformerPostProcessors
	 *
	 * @return ResourceConfigurator
	 * @see ResourcePostProcessorInterface
	 */
	public function setTransformerPostProcessors(array $transformerPostProcessors): ResourceConfigurator {
		$this->config->transformerPostProcessors = [];
		foreach ($transformerPostProcessors as $postProcessor)
			$this->addTransformerPostProcessor($postProcessor);
		return $this;
	}
	
	/**
	 * Adds a new transformer post processor class to the stack. The post processors are executed after a resource of
	 * the type you are currently configuring was transformed by the transformer instance. The post processors are
	 * called no matter if you are defining your own transformer class or using the automatic transformer.
	 *
	 * The class has to implement the ResourcePostProcessorInterface
	 *
	 * @param string $transformerPostProcessor
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 * @see ResourcePostProcessorInterface
	 */
	public function addTransformerPostProcessor(string $transformerPostProcessor): ResourceConfigurator {
		if (!in_array(ResourcePostProcessorInterface::class, class_implements($transformerPostProcessor)))
			throw new JsonApiException(
				"The given post processor: $transformerPostProcessor does not implement the required interface: " .
				ResourcePostProcessorInterface::class
			);
		$this->config->transformerPostProcessors[$transformerPostProcessor] = TRUE;
		return $this;
	}
	
	/**
	 * Returns the default number of items on a single page when the collection of this resource is requested
	 * @return int
	 */
	public function getPageSize(): int {
		return $this->config->pageSize;
	}
	
	/**
	 * Can be used to define the default number of items that are shown on a single page
	 * when the collection of this resource is requested
	 *
	 * @param int $pageSize
	 *
	 * @return ResourceConfigurator
	 */
	public function setPageSize(int $pageSize): ResourceConfigurator {
		$this->config->pageSize = $pageSize;
		return $this;
	}
	
	public function addAdditionalRoute(string $path, string $actionMethod, bool $handleAsCollection = FALSE, string $method = "GET"): RouteConfig {
		
		// Validate action method
		if (!method_exists($this->getControllerClass(), $actionMethod))
			throw new InvalidJsonApiConfigurationException("The given action method: $actionMethod for route: $path is not callable!");
		
		// Prepare route
		$path = trim(Path::unifySlashes($path), "/");
		if (stripos($path, $this->config->resourceType . "/") !== FALSE)
			$path = trim(substr($path, strlen($this->config->resourceType)));
		
		// Store the configuration
		$route = RouteConfig::makeNew($method, $path, $this->getControllerClass(), $actionMethod);
		$route->setName("json_api_additional_route_" . $this->getResourceType() . "-" . strtolower($method) . "-" . $path);
		$route->setStrategy(AdditionalRouteStrategy::class);
		$route->setAttribute("resourceType", $this->config->resourceType);
		$route->setAttribute("isCollection", $handleAsCollection);
		$this->additionalRoutes[$route->getName()] = $route;
		return $route;
	}
	
	/**
	 * Returns the list of registered, additional routes
	 * @return RouteConfig[]
	 */
	public function getAdditionalRoutes(): array {
		return $this->additionalRoutes;
	}
	
	public function removeAdditionalRoute(string $routeOrRouteName): ResourceConfigurator {
		if (isset($this->additionalRoutes[$routeOrRouteName])) unset($this->additionalRoutes[$routeOrRouteName]);
		else $this->additionalRoutes = array_filter($this->additionalRoutes, function (RouteConfig $v) use ($routeOrRouteName) {
			return $v->getPath() !== $routeOrRouteName;
		});
		return $this;
	}
}