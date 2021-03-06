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
 * Last modified: 2021.06.13 at 22:59
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Middleware\RequestCollectorMiddleware;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Routing\Util\MiniDispatcher;
use LaborDigital\T3fa\Core\Routing\Util\RequestRewriter;
use LaborDigital\T3fa\Middleware\Typo\ExtRedirectsMiddleware;
use LaborDigital\T3fa\Middleware\Typo\LanguageRewriterMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Middleware\BackendUserAuthenticator;
use TYPO3\CMS\Frontend\Middleware\FrontendUserAuthenticator;
use TYPO3\CMS\Frontend\Middleware\PageArgumentValidator;
use TYPO3\CMS\Frontend\Middleware\PageResolver;
use TYPO3\CMS\Frontend\Middleware\PrepareTypoScriptFrontendRendering;
use TYPO3\CMS\Frontend\Middleware\PreviewSimulator;
use TYPO3\CMS\Frontend\Middleware\SiteResolver;
use TYPO3\CMS\Frontend\Middleware\TypoScriptFrontendInitialization;

class ApiBootstrap implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    /**
     * The list of middlewares that should be executed on an api request in order to boot the basics of TYPO3
     *
     * @var string[]
     */
    public static $typoMiddlewares
        = [
            SiteResolver::class,
            // This middleware is internal and used to detect a manual language code through the L query param
            // or the x-t3fa-language header after the site was resolved by TYPO3
            LanguageRewriterMiddleware::class,
            BackendUserAuthenticator::class,
            FrontendUserAuthenticator::class,
            [ExtRedirectsMiddleware::class, 'registerIfRequired'],
            PageResolver::class,
            PreviewSimulator::class,
            PageArgumentValidator::class,
            RequestCollectorMiddleware::class,
            TypoScriptFrontendInitialization::class,
            PrepareTypoScriptFrontendRendering::class,
        ];
    
    /**
     * @var \LaborDigital\T3fa\Core\Routing\RouterFactory
     */
    protected $routerFactory;
    
    /**
     * @var \LaborDigital\T3fa\Core\Routing\Util\RequestRewriter
     */
    protected $requestRewriter;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(RouterFactory $routerFactory, RequestRewriter $requestRewriter, TypoContext $typoContext)
    {
        $this->routerFactory = $routerFactory;
        $this->requestRewriter = $requestRewriter;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Boots up all higher functions of TYPO3 and dispatches the API router request
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function boot(ServerRequestInterface $request): ResponseInterface
    {
        $request = $this->requestRewriter->rewriteHeadersToQueryParams($request);
        
        return $this->requestRewriter->runWithTypoEnvironment(
            function (ServerRequestInterface $typoRequest) use ($request) {
                $typoRequest = $this->prepareTypo($typoRequest);
                $request = $request->withAttribute('typoRequest', $typoRequest);
                
                return $this->routerFactory->getRouter()->dispatch($request);
            }, $request);
    }
    
    /**
     * Receives the rewritten request object and dispatches the required middlewares in order for TYPO3 to boot up only as much as we really need it.
     * This includes the site and page resolution, as well as the user login validation
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     * @throws \TYPO3\CMS\Core\Http\ImmediateResponseException
     */
    protected function prepareTypo(ServerRequestInterface $request): ServerRequestInterface
    {
        $dispatcher = $this->makeInstance(MiniDispatcher::class);
        $requestResolver = $this->buildRequestResolver();
        $dispatcher->middlewares = $this->buildTypoMiddlewareStack($requestResolver);
        
        $response = $dispatcher->handle($request);
        
        if ($requestResolver->typoRequest === null) {
            throw new T3faImmediateResponseException($response);
        }
        
        $dispatcher->middlewares = [];
        unset($dispatcher);
        
        // Ensure a cObject
        $GLOBALS['TSFE']->cObj = $this->makeInstance(
            ContentObjectRenderer::class, [$GLOBALS['TSFE'], $this->getContainer()]);
        
        return $requestResolver->typoRequest;
    }
    
    /**
     * Builds the prepared typo middleware stack based on static::$typoMiddlewares
     *
     * @param   object  $requestResolver
     *
     * @return array
     */
    protected function buildTypoMiddlewareStack(object $requestResolver): array
    {
        $middlewares = [];
        
        foreach (static::$typoMiddlewares as $middleware) {
            if (is_callable($middleware)) {
                $middleware = $middleware();
            }
            
            if (empty($middleware)) {
                continue;
            }
            
            $middlewares[] = $middleware;
        }
        
        $middlewares[] = $requestResolver;
        
        return $middlewares;
    }
    
    /**
     * Creates a anonymous class that will be executed as last middleware in the stack and fetch the prepared request object
     *
     * @return object
     */
    protected function buildRequestResolver(): object
    {
        return new class implements MiddlewareInterface {
            public $typoRequest;
            
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->typoRequest = $request;
                
                // This response does nothing, but is required for the contract to be fulfilled
                return new Response();
            }
        };
    }
}