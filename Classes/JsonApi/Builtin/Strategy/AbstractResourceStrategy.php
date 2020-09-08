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

use LaborDigital\Typo3BetterApi\Domain\BetterQuery\BetterQuery;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\CacheControllingStrategyTrait;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\RouteConfigAwareTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Pagination\PaginationAdapter;
use LaborDigital\Typo3FrontendApi\JsonApi\Pagination\PaginationException;
use LaborDigital\Typo3FrontendApi\JsonApi\Pagination\Paginator;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use League\Route\Http\Exception as HttpException;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route;
use League\Route\Strategy\AbstractStrategy;
use League\Route\Strategy\StrategyInterface;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use function GuzzleHttp\Psr7\stream_for;

abstract class AbstractResourceStrategy extends AbstractStrategy implements StrategyInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use RouteConfigAwareTrait;
    use CacheControllingStrategyTrait;

    /**
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
     */
    protected $transformerFactory;

    /**
     * AbstractResourceStrategy constructor.
     *
     * @param   \Psr\Http\Message\ResponseFactoryInterface                                $responseFactory
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory  $transformerFactory
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        TransformerFactory $transformerFactory
    ) {
        $this->responseFactory    = $responseFactory;
        $this->transformerFactory = $transformerFactory;
    }

    /**
     * @inheritDoc
     */
    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    /**
     * @inheritDoc
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    /**
     * @inheritDoc
     */
    public function getExceptionHandler(): MiddlewareInterface
    {
        return $this->getNullMiddleware();
    }

    /**
     * @inheritDoc
     */
    public function getThrowableHandler(): MiddlewareInterface
    {
        return $this->getNullMiddleware();
    }

    /**
     * Returns a empty middleware that does nothing, just to fill the gaps of this interface...
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    protected function getNullMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    /**
     * Return a middleware the creates a JSON response from an HTTP exception
     *
     * @param   HttpException  $exception
     *
     * @return MiddlewareInterface
     */
    protected function buildJsonResponseMiddleware(HttpException $exception): MiddlewareInterface
    {
        // As we have our own error handler we don't need this...
        return new class($exception) implements MiddlewareInterface {

            /**
             * @var \Throwable
             */
            protected $exception;

            /**
             * Exception middleware constructor.
             *
             * @param   \Throwable  $exception
             */
            public function __construct(Throwable $exception)
            {
                $this->exception = $exception;
            }

            /**
             * @param   \Psr\Http\Message\ServerRequestInterface  $request
             * @param   \Psr\Http\Server\RequestHandlerInterface  $handler
             *
             * @return \Psr\Http\Message\ResponseInterface
             */
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                throw $this->exception;
            }
        };
    }

    /**
     * Internal helper that makes sure that all the different database objects get unified into a query response
     * interface
     *
     * @param $response
     *
     * @return mixed|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    protected function convertDbResponse($response)
    {
        if ($response instanceof BetterQuery) {
            $response = $response->getQuery();
        }
        if ($response instanceof QueryInterface) {
            return $response->execute();
        }

        return $response;
    }

    /**
     * Internal factory to generate the context instance for the controller
     *
     * @param   string                                    $contextClass
     * @param   \League\Route\Route                       $route
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext|\LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    protected function getContextInstance(string $contextClass, Route $route, ServerRequestInterface $request)
    {
        $routeConfig     = $this->getRouteConfig($route);
        $routeAttributes = $routeConfig->getAttributes();
        if (! isset($routeAttributes["resourceType"])) {
            throw new JsonApiException("The given route: {$route->getName()} is not configured as a resource route!");
        }

        /** @var \LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext $context */
        $context = $this->getContainer()->get($contextClass);
        $context->setParams($route->getVars());
        $context->setResourceType($routeAttributes["resourceType"]);
        $resourceConfig = $this->configRepository->resource()->getResourceConfig($context->getResourceType());
        if (empty($resourceConfig)) {
            throw new JsonApiException("The given route: {$route->getName()} was marked as resource, but does not have a resource configuration mapped to it!");
        }
        $context->setResourceConfig($this->configRepository->resource()
                                                           ->getResourceConfig($context->getResourceType()));
        $context->setRequest($request);

        return $context;
    }

    /**
     * Internal factory to create a new fractal manager instance
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   string|null                               $resourceType
     * @param                                             $value
     *
     * @return \League\Fractal\Manager
     */
    protected function getManager(ServerRequestInterface $request, ?string $resourceType, $value): Manager
    {
        // Prepare the manager instance
        $manager = new Manager();

        // Check if we got sparse fieldSets
        $params = empty($request->getQueryParams()) ? [] : $request->getQueryParams();
        if (is_array($params["fields"])) {
            $manager->parseFieldsets($params["fields"]);
        }

        // Check if we got includes
        if (isset($params["include"])) {
            // Check for wildcard include
            if (stripos($params["include"], "*") !== false) {
                $existingParts     = Arrays::makeFromStringList($params["include"]);
                $transformer       = $this->transformerFactory->getTransformer($resourceType)
                                                              ->getConcreteTransformer($value);
                $transformerConfig = $transformer->getTransformerConfig();
                $includes          = [];
                // Get includes from the configuration
                if (! empty($transformerConfig->includes)) {
                    $includes = array_keys($transformerConfig->includes);
                }
                // Get hardcoded includes
                if (! empty($transformer->getAvailableIncludes())) {
                    $includes = array_merge($includes, $transformer->getAvailableIncludes());
                }
                // Re-add additionally given includes
                $includes          = array_merge($includes, array_filter($existingParts, function ($v) {
                    return $v !== "*";
                }));
                $params["include"] = implode(",", array_unique($includes));

            }
            $manager->parseIncludes($params["include"]);
        }

        // Prepare the serializer
        $baseUrl    = $request->getUri()->getScheme() . "://" . $request->getUri()->getHost() . "/" .
                      $this->configRepository->routing()->getRootUriPart() . "/" .
                      $this->configRepository->routing()->getResourceBaseUriPart() . "";
        $serializer = new JsonApiSerializer($baseUrl);
        $manager->setSerializer($serializer);

        // Done
        return $manager;
    }


    /**
     * Internal helper to create a new json response
     *
     * @param   array  $data
     * @param   int    $code
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getResponse(Route $route, array $data, int $code = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code);
        $response = $response->withHeader("Content-Type", "application/vnd.api+json");
        $response = $response->withBody(stream_for(json_encode($data, JSON_PRETTY_PRINT)));
        $response = $this->addInternalNoCacheHeaderIfRequired($route, $response);

        return $response;
    }

    /**
     * Internal helper which applies the pagination logic to a collection element
     *
     * @param   \League\Fractal\Resource\Collection                                                 $collection
     * @param   \Psr\Http\Message\ServerRequestInterface                                            $request
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext|null  $context
     */
    protected function paginateCollection(
        Collection $collection,
        ServerRequestInterface $request,
        ?CollectionControllerContext $context = null
    ) {
        $paginator = new Paginator($collection->getData());
        if (! is_null($context)) {
            if (! empty($context->getPageFinder())) {
                $paginator->setPageFinder($context->getPageFinder());
            }
            if (! empty($context->getPageSize())) {
                $paginator->setPageSize($context->getPageSize());
            }
        }
        $pagination = $paginator->paginateByRequest($request);
        $collection->setData($pagination->items);
        $collection->setPaginator(new PaginationAdapter($pagination, $request));
    }

    /**
     * Count the response items to check if we got a list where we should only get a single value
     *
     * @param $response
     *
     * @return int
     */
    protected function getResponseCount($response): int
    {
        if (empty($response)) {
            return 0;
        }
        if (! is_iterable($response)) {
            return 1;
        }
        try {
            $itemCount = (new Paginator($response))->getItemCount();
        } catch (PaginationException $exception) {
            return 1;
        }
        if (empty($itemCount)) {
            return 0;
        }

        return $itemCount;
    }
}
