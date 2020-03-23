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


use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class AdditionalRouteStrategy extends AbstractResourceStrategy {
	
	/**
	 * @inheritDoc
	 */
	public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface {
		// Get the resource configuration
		$context = $this->getContextInstance(ResourceControllerContext::class, $route, $request);
		$routeConfig = $this->getRouteConfig($route);
		
		// Check if we have to handle a collection
		$isCollection = (bool)$routeConfig->getAttributes()["isCollection"];
		if ($isCollection) $context = $this->getContextInstance(CollectionControllerContext::class, $route, $request);
		
		// Run the controller
		$controller = $route->getCallable($this->getContainer());
		$response = $controller($request, $context, $route->getVars());
		if ($response instanceof ResponseInterface) return $response;
		
		// Unify the response
		$response = $this->convertDbResponse($response);
		if (!$isCollection && $response instanceof QueryResultInterface) $response = $response->getFirst();
		
		// Prepare the transformation
		// IMPORTANT: NEVER - EVER - Set the $suggestedResourceType to the context's resource type. Otherwise the denied and allowed
		// properties automatically apply for all "additional routes" and not only for the element's they should apply to.
		$transformer = $this->transformerFactory->getTransformer();
		if ($isCollection) $item = new Collection($response, $transformer, $context->getResourceType());
		else $item = new Item($response, $transformer, $context->getResourceType());
		if (!empty($context->getMeta())) $item->setMeta($context->getMeta());
		
		// Make the manager
		$manager = $this->getManager($request, $context->getResourceType(), $response);
		$manager->setSerializer(new ArraySerializer());
		
		// Done
		return $this->getResponse($manager->createData($item)->toArray());
	}
	
}