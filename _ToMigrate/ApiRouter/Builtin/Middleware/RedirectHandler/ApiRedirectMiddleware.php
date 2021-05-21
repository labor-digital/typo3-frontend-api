<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.04.19 at 13:40
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\RedirectHandler;


use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Redirects\Http\Middleware\RedirectHandler;
use TYPO3\CMS\Redirects\Service\RedirectService;

class ApiRedirectMiddleware extends RedirectHandler {
	use ResponseFactoryTrait;
	
	/**
	 * @var \TYPO3\CMS\Redirects\Service\RedirectService
	 */
	protected $redirectService;
	
	/**
	 * ApiRedirectMiddleware constructor.
	 *
	 * @param \TYPO3\CMS\Redirects\Service\RedirectService $redirectService
	 */
	public function __construct(RedirectService $redirectService) {
		$this->redirectService = $redirectService;
	}
	
	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		
		// Handle the real request -> by converting the request to have the slug as path
		$queryParams = $request->getQueryParams();
		$realRequest = $request;
		if (!empty($queryParams["slug"]))
			$realRequest = $request->withUri($request->getUri()->withPath($queryParams["slug"])->withQuery(""));
		
		// Build redirect
		$port = $realRequest->getUri()->getPort();
		$matchedRedirect = $this->redirectService->matchRedirect(
			$realRequest->getUri()->getHost() . ($port ? ':' . $port : ''),
			$realRequest->getUri()->getPath(),
			$realRequest->getUri()->getQuery() ?? ''
		);
		
		// If the matched redirect is found, resolve it, and check further
		if (is_array($matchedRedirect)) {
			$url = $this->redirectService->getTargetUrl($matchedRedirect, $request->getQueryParams(), $request->getAttribute('site', NULL));
			if ($url instanceof UriInterface) {
				$this->logger->debug('Redirecting API request', ['record' => $matchedRedirect, 'uri' => $url]);
				$response = $this->buildRedirectResponse($url, $matchedRedirect);
				$this->incrementHitCount($matchedRedirect);
				
				return $response;
			}
		}
		
		// Done
		return $handler->handle($request);
	}
	
	/**
	 * @inheritDoc
	 */
	protected function buildRedirectResponse(UriInterface $uri, array $redirectRecord): ResponseInterface {
		// Tell the frontend framework we have additional information for it
		// and tell it to redirect the request to another location using a redirect.
		// The frontend framework has to decide how to handle this response...
		return $this->getJsonApiResponse([
			"data" => [
				"type"       => "control",
				"id"         => md5((string)$uri),
				"attributes" => [
					"type"   => "redirect",
					"target" => (string)$uri,
					"code"   => (int)$redirectRecord['target_statuscode'],
				],
			],
		], 203);
	}
	
	
}