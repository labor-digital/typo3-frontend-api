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
 * Last modified: 2019.09.19 at 15:38
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\FrontendSimulation;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator;
use LaborDigital\Typo3FrontendApi\Event\FrontendSimulationMiddlewareFilterEvent;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\EventBus\EventBusInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Frontend\Middleware\PageResolver;

class FrontendSimulationMiddleware implements MiddlewareInterface, SingletonInterface {
	
	/**
	 * The list of dokTypes we allow in our lookups
	 * @var array
	 */
	public static $allowedDokTypes = [1, 6];
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator
	 */
	protected $simulator;
	
	/**
	 * @var \TYPO3\CMS\Core\Routing\SiteMatcher
	 */
	protected $siteMatcher;
	
	/**
	 * @var \Neunerlei\EventBus\EventBusInterface
	 */
	protected $eventBus;
	
	/**
	 * A cache object to hold the resolved pid by the slug name
	 * this should lessen the load when multiple chained api requests are handled.
	 * @var array
	 */
	protected $slugCache = [];
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * FrontendSimulationMiddleware constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator         $simulator
	 * @param \TYPO3\CMS\Core\Routing\SiteMatcher                                  $siteMatcher
	 * @param \Neunerlei\EventBus\EventBusInterface                                $eventBus
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 */
	public function __construct(EnvironmentSimulator $simulator, SiteMatcher $siteMatcher,
								EventBusInterface $eventBus, FrontendApiConfigRepository $configRepository) {
		$this->simulator = $simulator;
		$this->siteMatcher = $siteMatcher;
		$this->eventBus = $eventBus;
		$this->configRepository = $configRepository;
	}
	
	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		
		// Get the current language
		/** @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage $language */
		$language = $request->getAttribute("language");
		
		// Preset the page id
		/** @var \TYPO3\CMS\Core\Site\Entity\Site $site */
		$site = $request->getAttribute("site");
		
		// Try again with changed http/https scheme if we did not get a site
		if ($site instanceof NullSite) {
			// Check if we can find a forwarded site for a proxy
			$knownHost = $request->getUri()->getHost();
			$server = $request->getServerParams();
			if (!empty($server["HTTP_X_HOST"]) && $server["HTTP_X_HOST"] !== $knownHost) {
				// Update the host
				$uri = $request->getUri();
				$uri = $uri->withHost($server["HTTP_X_HOST"]);
				
				// Try to find the site again
				$subRequest = $request->withUri($uri);
				$siteRouteResult = $this->siteMatcher->matchRequest($subRequest);
				
				// Try to modify the scheme if we did not find the site
				if ($siteRouteResult->getSite() instanceof NullSite) {
					// Check if we also got a HTTP_X_FORWARDED_PROTO
					if (!empty($server["HTTP_X_FORWARDED_PROTO"]))
						$uri = $uri->withScheme($server["HTTP_X_FORWARDED_PROTO"]);
					else {
						// Try again with changed http/https scheme if we did not get a site
						$uri = $uri->withScheme($uri->getScheme() === "http" ? "https" : "http");
					}
					
					// Try to find the site again
					$subRequest = $request->withUri($uri);
					$siteRouteResult = $this->siteMatcher->matchRequest($subRequest);
				}
				
				// Update the site if we got it correctly now
				if (!$siteRouteResult->getSite() instanceof NullSite) {
					$site = $siteRouteResult->getSite();
					$request = $request->withAttribute("site", $site);
				} else {
					// I can't help you here, pal!
					throw new BadRequestException("Could not find a site for the given uri!");
				}
			}
		}
		
		// Get the query params
		$queryParams = $request->getQueryParams();
		
		// Check if we got a slug
		if (!empty($queryParams["slug"])) {
			$slug = $queryParams["slug"];
			if (!isset($this->slugCache[$slug])) {
				try {
					// Simulate a request on the slug to match the site
					$subRequest = $request->withUri($request->getUri()->withPath($slug)->withQuery(""));
					/** @var \TYPO3\CMS\Core\Routing\SiteRouteResult $siteRouteResult */
					$siteRouteResult = $this->siteMatcher->matchRequest($subRequest);
					$site = $siteRouteResult->getSite();
					
					// Fail if we could not find a matching site
					if (!method_exists($site, "getRouter"))
						throw new BadRequestException("Could not find the getRouter method on the site object!");
					/** @var \TYPO3\CMS\Core\Routing\PageRouter $router */
					$router = $site->getRouter();
					/** @var \TYPO3\CMS\Core\Routing\PageArguments $pageArguments */
					$pageArguments = $router->matchRequest($subRequest, $siteRouteResult);
					if (!$pageArguments->getPageId()) throw new NotFoundException();
					
					// Merge route arguments into the incoming query
					$request = $request->withQueryParams(Arrays::merge(
						$queryParams, $pageArguments->getRouteArguments()));
					$queryParams = $request->getQueryParams();
					
					// Update language
					$language = $siteRouteResult->getLanguage();
					
					// Update pid
					$pageId = $pageArguments->getPageId();
				} catch (RouteNotFoundException $exception) {
					throw new NotFoundException("Not Found", $exception);
				}
				
				// Store the cache
				$this->slugCache[$slug] = [$language, $pageId];
			} else {
				[$language, $pageId] = $this->slugCache[$slug];
			}
		} else {
			
			// Try to find page id by query parameters
			if (is_numeric($queryParams["pid"]))
				$pageId = (int)$queryParams["pid"];
			else {
				// Try to find the pid for the page resource query
				$routing = $this->configRepository->routing();
				$expectedUri = $routing->getRootUriPart() . "/" . $routing->getResourceBaseUriPart();
				$expectedUri .= "/page/";
				if (preg_match("~$expectedUri(\d+)(/|$)~", $request->getUri()->getPath(), $m))
					$pageId = (int)$m[1];
			}
		}
		
		// Update the id in the query parameters
		$queryParams["id"] = $pageId;
		
		// Check if we got an L parameter
		if (isset($queryParams["L"])) {
			if (strlen($queryParams["L"]) > 2 && !is_numeric($queryParams["L"]))
				throw new BadRequestException("The given language parameter seems to be invalid!");
			$language = $queryParams["L"];
		}
		
		// Update the query parameters in the request
		$request = $request->withQueryParams($queryParams);
		
		// Update global request
		$GLOBALS["TYPO3_REQUEST"] = $request;
		$GLOBALS["TYPO3_REQUEST_FALLBACK"] = $request;
		
		// Allow filtering
		$this->eventBus->dispatch(($e = new FrontendSimulationMiddlewareFilterEvent($language, $pageId, $request)));
		$language = $e->getLanguage();
		$pageId = $e->getPid();
		$request = $e->getRequest();
		
		// Update global request, again
		$GLOBALS["TYPO3_REQUEST"] = $request;
		$GLOBALS["TYPO3_REQUEST_FALLBACK"] = $request;
		
		// Simulate the request
		try {
			return $this->simulator->runWithEnvironment([
				"pid"      => $pageId,
				"language" => $language,
			], function () use ($handler, $request) {
				
				// Reroute the request through the page resolver
				$dummyHandler = new class(static::$allowedDokTypes, $handler) implements RequestHandlerInterface {
					/**
					 * @var array
					 */
					protected $allowedDokTypes;
					
					/**
					 * @var \Psr\Http\Server\RequestHandlerInterface
					 */
					protected $handler;
					
					/**
					 * Dummy handler constructor.
					 *
					 * @param array                                    $allowedDokTypes
					 * @param \Psr\Http\Server\RequestHandlerInterface $handler
					 */
					public function __construct(array $allowedDokTypes, RequestHandlerInterface $handler) {
						$this->allowedDokTypes = $allowedDokTypes;
						$this->handler = $handler;
					}
					
					/**
					 * @inheritDoc
					 */
					public function handle(ServerRequestInterface $request): ResponseInterface {
						// Update global request, one last time
						$GLOBALS["TYPO3_REQUEST"] = $request;
						$GLOBALS["TYPO3_REQUEST_FALLBACK"] = $request;
						
						// Check if this is an allowed page type
						$dokType = (int)$GLOBALS["TSFE"]->page["doktype"];
						if (!in_array($dokType, $this->allowedDokTypes))
							throw new BadRequestException("The dokType of the given page is not allowed!");
						
						// Handle the request
						return $this->handler->handle($request);
					}
				};
				
				// Let the page resolver handle the new, updated frontend page
				$handler = TypoContainer::getInstance()->get(PageResolver::class);
				return $handler->process($request, $dummyHandler);
				
			});
		} catch (ImmediateResponseException $exception) {
			// Handle 403 exceptions on pages (access denied) as 404 -> Page not found
			if ($exception->getResponse()->getStatusCode() === 403)
				throw new NotFoundException($exception->getResponse()->getReasonPhrase());
			throw $exception;
		} catch (Throwable $exception) {
			throw $exception;
		}
	}
	
}