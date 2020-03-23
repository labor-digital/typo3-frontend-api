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
 * Last modified: 2019.08.06 at 17:09
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter;


use GuzzleHttp\Psr7\ServerRequest;
use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\Whoops\ErrorHandler;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class ApiRouter {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\Whoops\ErrorHandler
	 */
	protected $errorHandler;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
	 */
	protected $container;
	
	/**
	 * ApiRouter constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 * @param \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface        $container
	 * @param \LaborDigital\Typo3FrontendApi\Whoops\ErrorHandler                   $errorHandler
	 */
	public function __construct(FrontendApiConfigRepository $configRepository, TypoContainerInterface $container, ErrorHandler $errorHandler) {
		$this->errorHandler = $errorHandler;
		$this->configRepository = $configRepository;
		$this->container = $container;
	}
	
	/**
	 * Receives an api path (without hostname) and checks if it starts with the configured root uri.
	 * If so it will return true, false if not.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function canHandlePath(string $path): bool {
		return (bool)preg_match("~(?:^/?)" . preg_quote($this->configRepository->routing()->getRootUriPart()) . "/~si", $path);
	}
	
	/**
	 * This method is used for internal lookups of data sets by the ResourceDataRepository.
	 * It will receive a configured link, generate a server request object out of it
	 * and then pass it into the handler() to dispatch the middleware stack.
	 *
	 * The resulting response object will be returned.
	 *
	 * NOTE: This method is basically a simulator for real api requests, that follows certain constraints.
	 * 1. $_POST and $_FILES will be always empty when the link is handled
	 * 2. $_GET is set to the query string that is passed by the link.
	 * 3. The request method will be overwritten and always be GET.
	 *
	 * NOTE 2: The changes to the superGlobal arrays will automatically be reverted after the handle() method generated
	 * the response object.
	 *
	 * @param \Psr\Http\Message\UriInterface $link
	 * @param string                         $middlewareStack
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function handleLink(UriInterface $url, string $middlewareStack = "external"): ResponseInterface {
		// Prepare the path
		if (!$this->canHandlePath($url->getPath()))
			$url = $url->withPath($this->configRepository->routing()->getRootUriPart() . "/" . ltrim($url->getPath(), "/"));
		
		// Clean the environment
		$rootRequest = $GLOBALS["TYPO3_REQUEST"];
		$backup = [];
		foreach (["_POST", "_GET", "_FILES", "TYPO3_REQUEST"] as $k) {
			$backup[$k] = $GLOBALS[$k];
			$GLOBALS[$k] = [];
		}
		$backup["_SERVER"] = $_SERVER;
		
		// Simulate request
		parse_str($url->getQuery(), $parts);
		if (is_array($parts)) $_GET = $parts;
		
		// Simulate server request
		$relativeUrl = substr((string)$url, strlen($url->getHost() . $url->getScheme()) + 3);
		$_SERVER["REQUEST_URI"] = $relativeUrl;
		if (!empty($url->getQuery())) $_SERVER["QUERY_STRING"] = $url->getQuery();
		
		// Create the request
		$request = empty($rootRequest) ? ServerRequest::fromGlobals() : $rootRequest;
		$request = $request->withMethod("GET");
		$request = $request->withUri($url);
		$request = $request->withQueryParams($parts);
		$GLOBALS["TYPO3_REQUEST"] = $request;
		
		// Handle the request
		$result = $this->handle($request, $middlewareStack);
		
		// Restore the backup
		foreach ($backup as $k => $v)
			$GLOBALS[$k] = $v;
		unset($backup);
		
		// Done
		return $result;
	}
	
	/**
	 * Passes the given server request interface to our internal router instance and dispatches the middleware stack.
	 * It will return the generated response object back, after the stack was executed.
	 *
	 * @param ServerRequestInterface $request
	 * @param string                 $middlewareStack There are two different "stacks" of middlewares.
	 *                                                - "external" is used for all external API requests using the URL.
	 *                                                Here you may register additional auth middlewares to make
	 *                                                sure only certain users can interact with the content.
	 *                                                - "internal" is used when you access data through the
	 *                                                "ResourceDataRepository" or by manually handling a request
	 *                                                that sets the stack to "internal". Internal expects the request
	 *                                                to be authenticated and will not cache the data.
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function handle(ServerRequestInterface $request, string $middlewareStack = "external"): ResponseInterface {
		return $this->errorHandler->handleErrorsIn(function () use ($request, $middlewareStack) {
			$router = $this->container->get(Router::class);
			$this->configRepository->routing()->prepareRouter($router, $middlewareStack);
			return $router->dispatch($request);
		}, $request);
	}
}