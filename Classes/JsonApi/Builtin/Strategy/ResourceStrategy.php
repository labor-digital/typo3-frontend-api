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
 * Last modified: 2019.08.27 at 00:43
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Strategy;


use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\InvalidJsonApiConfigurationException;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class ResourceStrategy extends AbstractResourceStrategy {
	/**
	 * @inheritDoc
	 */
	public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface {
		// Get the resource configuration
		$context = $this->getContextInstance(ResourceControllerContext::class, $route, $request);
		
		// Run the controller
		$controller = $route->getCallable($this->getContainer());
		$id = $route->getVars()["id"];
		$response = $controller($request, (is_numeric($id) ? (int)$id : $id), $context);
		if ($response instanceof ResponseInterface)
			return $this->addInternalNoCacheHeaderIfRequired($route, $response);
		
		// Unify the response
		$response = $this->convertDbResponse($response);
		if ($response instanceof QueryResultInterface) $response = $response->getFirst();
		
		// Validate the item count
		$responseCount = $this->getResponseCount($response);
		if ($responseCount > 1)
			throw new InvalidJsonApiConfigurationException("You returned more than one objects from a resource controller action.");
		if ($responseCount === 0) throw new NotFoundException();
		
		// Make the item
		$transformer = $this->transformerFactory->getTransformer($context->getResourceType());
		$item = new Item($response, $transformer, $context->getResourceType());
		if (!empty($context->getMeta())) $item->setMeta($context->getMeta());
		
		// Make the manager
		$manager = $this->getManager($request, $context->getResourceType(), $response);
		
		// Check what we have to look up
		if (is_string($route->getVars()["relationship"])) {
			
			// Handle relationship table
			$propertyName = $route->getVars()["relationship"];
			$manager->parseIncludes($propertyName);
			$data = $manager->createData($item)->toArray();
			$relationship = Arrays::getPath($data, ["data", "relationships", $propertyName], NULL);
			if (empty($relationship)) throw new NotFoundException();
			return $this->getResponse($route, $relationship);
			
		} else if (is_string($route->getVars()["related"])) {
			// Handle the list of related objects
			$propertyName = $route->getVars()["related"];
			
			// Get the child data of the related property
			$config = $this->transformerFactory->getConfigFor($response, $context->getResourceType());
			if (!isset($config->includes[$propertyName])) throw new NotFoundException("Relation for property: $propertyName was not found!");
			$propertyConf = $config->includes[$propertyName];
			$data = call_user_func($propertyConf["getter"], $response);
			
			// Build the sub item
			$transformer = $this->transformerFactory->getTransformer($propertyConf["resourceType"]);
			if ($propertyConf["isCollection"]) $subItem = new Collection($data, $transformer, $propertyConf["resourceType"]);
			else $subItem = new Item($data, $transformer, $propertyConf["resourceType"]);
			
			// Handle pagination if required
			if ($subItem instanceof Collection)
				$this->paginateCollection($subItem, $request);
			
			// Done
			return $this->getResponse($route, $manager->createData($subItem)->toArray());
		} else {
			// Find the data of a resource
			return $this->getResponse($route, $manager->createData($item)->toArray());
		}
	}
	
}