<?php
declare(strict_types=1);
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


use LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\Utility\CallbackMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\Utility\DelegateMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\Utility\MiniDispatcher;
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
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Middleware\PageResolver;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

class FrontendSimulationMiddleware implements MiddlewareInterface, SingletonInterface
{
    /**
     * The header where the frontend can submit it's current language to
     */
    public const REQUEST_LANGUAGE_HEADER = 'x-t3fa-language';

    /**
     * The list of dokTypes we allow in our lookups
     *
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
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator
     */
    protected $cacheHashCalculator;

    /**
     * A cache object to hold the resolved pid by the slug name
     * this should lessen the load when multiple chained api requests are handled.
     *
     * @var array
     */
    protected $slugCache = [];


    /**
     * FrontendSimulationMiddleware constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator          $simulator
     * @param   \TYPO3\CMS\Core\Routing\SiteMatcher                                   $siteMatcher
     * @param   \Neunerlei\EventBus\EventBusInterface                                 $eventBus
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     * @param   \TYPO3\CMS\Frontend\Page\CacheHashCalculator                          $cacheHashCalculator
     */
    public function __construct(
        EnvironmentSimulator $simulator,
        SiteMatcher $siteMatcher,
        EventBusInterface $eventBus,
        FrontendApiConfigRepository $configRepository,
        CacheHashCalculator $cacheHashCalculator
    ) {
        $this->simulator           = $simulator;
        $this->siteMatcher         = $siteMatcher;
        $this->eventBus            = $eventBus;
        $this->configRepository    = $configRepository;
        $this->cacheHashCalculator = $cacheHashCalculator;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Preset the page id
        $site = $this->findSite($request);

        // Update the query parameters in the request
        $request = $this->findRouteAttributes($request, $site);

        // Run the outer dispatcher
        $dispatcher                    = GeneralUtility::makeInstance(MiniDispatcher::class);
        $updateGlobalRequestMiddleware = GeneralUtility::makeInstance(UpdateGlobalRequestMiddleware::class);
        $dispatcher->middlewares[]     = $updateGlobalRequestMiddleware;
        $dispatcher->middlewares[]     = GeneralUtility::makeInstance(CallbackMiddleware::class,
            function (ServerRequestInterface $request) {
                $this->eventBus->dispatch(($e = new FrontendSimulationMiddlewareFilterEvent(
                    $request->getAttribute('t3fa.language'),
                    $request->getAttribute('t3fa.pid'),
                    $request
                )));

                $request = $e->getRequest();
                $request = $request->withAttribute('t3fa.pid', $e->getPid());
                $request = $request->withAttribute('t3fa.language', $e->getLanguage());

                return $request;
            });
        $dispatcher->middlewares[]     = $updateGlobalRequestMiddleware;
        $dispatcher->middlewares[]     = GeneralUtility::makeInstance(CallbackMiddleware::class,
            function (ServerRequestInterface $request) use ($handler) {
                return $this->simulator->runWithEnvironment([
                    'pid'      => $request->getAttribute('t3fa.pid'),
                    'language' => $request->getAttribute('t3fa.language'),
                ], function () use ($handler) {
                    try {
                        // Run the inner dispatcher
                        $dispatcher                = GeneralUtility::makeInstance(MiniDispatcher::class);
                        $dispatcher->middlewares[] = GeneralUtility::makeInstance(PageResolver::class);
                        $dispatcher->middlewares[] = GeneralUtility::makeInstance(CallbackMiddleware::class,
                            function (ServerRequestInterface $request) {
                                // Update tsfe controller chash array
                                /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
                                $tsfe              = $GLOBALS['TSFE'];
                                $tsfe->cHash_array = $this->cacheHashCalculator
                                    ->getRelevantParameters(
                                        HttpUtility::buildQueryString($request->getQueryParams())
                                    );

                                return $request;
                            });
                        $dispatcher->middlewares[] = GeneralUtility::makeInstance(UpdateGlobalRequestMiddleware::class);
                        $dispatcher->middlewares[] = GeneralUtility::makeInstance(DelegateMiddleware::class, $handler);

                        return $dispatcher->handle($GLOBALS['TYPO3_REQUEST']);
                    } catch (ImmediateResponseException $exception) {
                        // Handle 403 exceptions on pages (access denied) as 404 -> Page not found
                        if ($exception->getResponse()->getStatusCode() === 403) {
                            throw new NotFoundException($exception->getResponse()->getReasonPhrase(), $exception);
                        }
                        throw $exception;
                    }
                });
            });

        return $dispatcher->handle($request);
    }

    /**
     * Finds the current site based on the given request
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteInterface
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    protected function findSite(ServerRequestInterface $request): SiteInterface
    {
        /** @var \TYPO3\CMS\Core\Site\Entity\Site $site */
        $site = $request->getAttribute('site');

        // Try again with changed http/https scheme if we did not get a site
        if ($site instanceof NullSite) {
            // Check if we can find a forwarded site for a proxy
            $knownHost = $request->getUri()->getHost();
            $server    = $request->getServerParams();
            if (! empty($server['HTTP_X_HOST']) && $server['HTTP_X_HOST'] !== $knownHost) {
                // Update the host
                $uri = $request->getUri();
                $uri = $uri->withHost($server['HTTP_X_HOST']);

                // Try to find the site again
                $subRequest      = $request->withUri($uri);
                $siteRouteResult = $this->siteMatcher->matchRequest($subRequest);

                // Try to modify the scheme if we did not find the site
                if ($siteRouteResult->getSite() instanceof NullSite) {
                    // Check if we also got a HTTP_X_FORWARDED_PROTO
                    if (! empty($server['HTTP_X_FORWARDED_PROTO'])) {
                        $uri = $uri->withScheme($server['HTTP_X_FORWARDED_PROTO']);
                    } else {
                        // Try again with changed http/https scheme if we did not get a site
                        $uri = $uri->withScheme($uri->getScheme() === 'http' ? 'https' : 'http');
                    }

                    // Try to find the site again
                    $subRequest      = $request->withUri($uri);
                    $siteRouteResult = $this->siteMatcher->matchRequest($subRequest);
                }

                // Update the site if we got it correctly now
                if (! $siteRouteResult->getSite() instanceof NullSite) {
                    $site = $siteRouteResult->getSite();
                } else {
                    // I can't help you here, pal!
                    throw new BadRequestException('Could not find a site for the given uri!');
                }
            }
        }

        return $site;
    }

    /**
     * Reads the incoming request and adds additional attributes based on the parameters.
     * This will handle the slug resolution as well.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface   $request
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteInterface  $site
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     * @throws \League\Route\Http\Exception\BadRequestException
     * @throws \League\Route\Http\Exception\NotFoundException
     */
    protected function findRouteAttributes(ServerRequestInterface $request, SiteInterface $site): ServerRequestInterface
    {
        // Get the current language
        /** @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage $language */
        $language = $request->getAttribute('language');

        // Inherit information based on a given slug
        $queryParams = $request->getQueryParams();
        $hasSlug     = ! empty($queryParams['slug']);
        if ($hasSlug) {
            $slug = $queryParams['slug'];
            if (! isset($this->slugCache[$slug])) {
                try {
                    // Remove superfluous / or /index.html from the tail of a slug
                    $slug = '/' . ltrim(preg_replace('~(.*)/(?:index\.x?html?)?$~i', '$1', $slug), '/');

                    // Simulate a request on the slug to match the site
                    $subRequest = $request->withUri($request->getUri()->withPath($slug)->withQuery(''));
                    /** @var \TYPO3\CMS\Core\Routing\SiteRouteResult $siteRouteResult */
                    $siteRouteResult = $this->siteMatcher->matchRequest($subRequest);
                    $site            = $siteRouteResult->getSite();

                    // Fail if we could not find a matching site
                    if (! method_exists($site, 'getRouter')) {
                        throw new BadRequestException('Could not find the getRouter method on the site object!');
                    }
                    /** @var \TYPO3\CMS\Core\Routing\PageRouter $router */
                    $router = $site->getRouter();
                    /** @var \TYPO3\CMS\Core\Routing\PageArguments $pageArguments */
                    $pageArguments = $router->matchRequest($subRequest, $siteRouteResult);
                    if (! $pageArguments->getPageId()) {
                        throw new NotFoundException();
                    }

                    // Merge route arguments into the incoming query
                    $request     = $request->withQueryParams(
                        Arrays::merge($queryParams, $pageArguments->getRouteArguments())
                    );
                    $queryParams = $request->getQueryParams();

                    // Update language
                    $language = $siteRouteResult->getLanguage();

                    // Update pid
                    $pageId = $pageArguments->getPageId();
                } catch (RouteNotFoundException $exception) {
                    throw new NotFoundException('Not Found', $exception);
                }

                // Store the cache
                $this->slugCache[$slug] = [$language, $pageId];
            } else {
                [$language, $pageId] = $this->slugCache[$slug];
            }
        } elseif (is_numeric($queryParams['pid'])) {
            $pageId = (int)$queryParams['pid'];
        } else {
            // Try to find the pid for the page resource query
            $routing     = $this->configRepository->routing();
            $expectedUri = $routing->getRootUriPart() . '/' . $routing->getResourceBaseUriPart();
            $expectedUri .= '/page/';
            if (preg_match('~' . $expectedUri . '(\d+)(/|$)~', $request->getUri()->getPath(), $m)) {
                $pageId = (int)$m[1];
            } else {
                $pageId = $site->getRootPageId();
            }
        }

        // Check if we got an L parameter or a language header
        if (! $hasSlug) {
            foreach (
                [
                    // The L parameter has precedence over the header -> Therefore it must be checked first
                    'parameter' => $queryParams['L'] ?? null,
                    'header'    => $request->getHeaderLine(static::REQUEST_LANGUAGE_HEADER),
                ] as $type => $provider
            ) {
                if (empty($provider) && $provider !== '0') {
                    continue;
                }
                if (is_string($provider)) {
                    if (is_numeric($provider) && strlen($provider) <= 2) {
                        $language = (int)$provider;
                        break;
                    }

                    if (ctype_alpha($provider) && strlen($provider) === 2) {
                        $language = strtolower($provider);
                        break;
                    }

                    throw new BadRequestException('The given language ' . $type . ' seems to be invalid!');
                }
            }
        }

        // Update the request
        $queryParams['id'] = $pageId;
        $request           = $request->withAttribute('site', $site);
        $request           = $request->withAttribute('t3fa.pid', $pageId);
        $request           = $request->withAttribute('t3fa.language', $language);
        $request           = $request->withQueryParams($queryParams);

        return $request;
    }
}
