<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.05.17 at 13:05
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Middleware\RequestCollectorMiddleware;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Routing\Util\MiniDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Frontend\Middleware\PageArgumentValidator;
use TYPO3\CMS\Frontend\Middleware\PageResolver;
use TYPO3\CMS\Frontend\Middleware\PreviewSimulator;

class ApiBootstrap implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    public static $typoMiddlewares
        = [
            PageResolver::class,
            PreviewSimulator::class,
            PageArgumentValidator::class,
            RequestCollectorMiddleware::class,
        ];
    
    /**
     * @var \LaborDigital\T3fa\Core\Routing\RouterFactory
     */
    protected $routerFactory;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(RouterFactory $routerFactory, TypoContext $typoContext)
    {
        $this->routerFactory = $routerFactory;
        $this->typoContext = $typoContext;
    }
    
    public function boot(ServerRequestInterface $request): ResponseInterface
    {
        $typoRequest = $this->makeTypoRequest($request);
        $request = $request->withAttribute('typoRequest', $typoRequest);
        
        $this->prepareTypo($request);
    }
    
    protected function prepareTypo(ServerRequestInterface $request): void
    {
        $dispatcher = $this->makeInstance(MiniDispatcher::class);
        
        foreach (static::$typoMiddlewares as $middleware) {
            $dispatcher->middlewares[] = $this->getService($middleware);
        }
        
        $dispatcher->middlewares = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                // This response does nothing, but is required for the contract to be fulfilled
                return new Response();
            }
        };
        
        $dispatcher->handle($request);
    }
    
    protected function makeTypoRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        dbge($request);
    }
    
}