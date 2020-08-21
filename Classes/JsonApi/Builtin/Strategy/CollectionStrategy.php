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
use LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataResult;
use League\Fractal\Resource\Collection;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CollectionStrategy extends AbstractResourceStrategy
{
    /**
     * @inheritDoc
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        // Get the resource configuration
        /** @var CollectionControllerContext $context */
        $context = $this->getContextInstance(CollectionControllerContext::class, $route, $request);

        // Prepare pagination from resource type
        if (! empty($context->getResourceConfig())) {
            $context->setPageSize($context->getResourceConfig()->pageSize);
        }

        // Run the controller
        $controller = $route->getCallable($this->getContainer());
        $response   = $controller($request, $context);

        // Pass through direct responses
        if ($response instanceof ResourceDataResult) {
            return $this->getResponse($route, $response->getData(true));
        }
        if ($response instanceof ResponseInterface) {
            return $this->addInternalNoCacheHeaderIfRequired($route, $response);
        }

        // Unify the response
        $response = $this->convertDbResponse($response);

        // Prepare pagination
        $transformer = $this->transformerFactory->getTransformer($context->getResourceType());
        $collection  = new Collection($response, $transformer, $context->getResourceType());
        if (! empty($context->getMeta())) {
            $collection->setMeta($context->getMeta());
        }
        $this->paginateCollection($collection, $request, $context);

        // Make the manager
        $manager = $this->getManager($request, $context->getResourceType(), $response);

        // Done
        return $this->getResponse($route, $manager->createData($collection)->toArray());
    }

}
