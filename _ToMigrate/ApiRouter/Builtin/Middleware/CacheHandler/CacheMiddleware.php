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
 * Last modified: 2019.08.09 at 09:55
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\CacheHandler;


use LaborDigital\Typo3BetterApi\Cache\FrontendCache;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Class CacheMiddleware
 *
 * @package    LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\CacheHandler
 * @deprecated no longer actually in use. Will be removed in v10
 */
class CacheMiddleware implements MiddlewareInterface
{

    /**
     * The tag we are writing our cache entries with
     */
    public const CACHE_TAG = "typo3FrontendApi";

    /**
     * If this header is present in the response object it will not be cached
     */
    public const CACHE_CONTROL_HEADER = "X-FRONTEND-API-CACHE";

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $context;

    /**
     * @var \LaborDigital\Typo3BetterApi\Cache\FrontendCache
     */
    protected $frontendCache;

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * CacheMiddleware constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Cache\FrontendCache                      $frontendCache
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext                  $context
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(FrontendCache $frontendCache, TypoContext $context, FrontendApiConfigRepository $configRepository)
    {
        $this->context          = $context;
        $this->frontendCache    = $frontendCache;
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check if we can handle this request by our cache
        $searchInServerCache = in_array(strtoupper($request->getMethod()), ["GET", "HEAD"]);
        $writeBrowserCache   = ! $this->context->getBeUserAspect()->isLoggedIn() &&
                               ! $this->context->getFeUserAspect()->isLoggedIn();

        // Check if the server cache was disabled by setting the no cache pragma
        $writeServerCache = $searchInServerCache;
        if ($searchInServerCache && $this->context->getBeUserAspect()->isLoggedIn() && $this->context->getBeUserAspect()->isAdmin()) {
            if (stripos($request->getHeaderLine("cache-control"), "no-cache") !== false) {
                $searchInServerCache = false;
            } elseif (stripos($request->getHeaderLine("pragma"), "no-cache") !== false) {
                $searchInServerCache = false;
            }
        }

        // Make sure to remove typos cache headers to ensure we have a blank slate to work with
        foreach (["Expires", "Last-Modified", "Cache-Control", "Pragma"] as $key) {
            header_remove($key);
        }

        // Generate the cache key if the request is cacheable
        $cacheKey = $this->makeCacheKey($request);

        // Check if we can respond with the cached value
        if ($searchInServerCache && $this->frontendCache->has($cacheKey)) {
            /** @var array $entry */
            $entry = $this->frontendCache->get($cacheKey);
            /** @var $response ResponseInterface */
            $response = $entry["response"]->withBody(stream_for($entry["body"]));
            $response = $response->withHeader("T3fa-Server-Cache-Status", "hit");

        } else {
            // Handle the request
            /** @var $response ResponseInterface */
            $response = $handler->handle($request);

            // Check if the handler disabled the cache
            if ($writeServerCache && in_array("off", $response->getHeader(static::CACHE_CONTROL_HEADER))) {
                $response         = $response->withoutHeader(static::CACHE_CONTROL_HEADER);
                $writeServerCache = false;
            }

            // Check if the response is a cacheable code
            if ($writeServerCache && ! in_array($response->getStatusCode(), [200, 203, 204, 206])) {
                $writeServerCache = false;
            }

            // Store the generation timestamp
            $response = $response->withHeader("T3fa-Generated", gmdate("D, d M Y H:i:s \G\M\T"));

            // Store the response object
            if ($writeServerCache) {
                $entry = [
                    "body"     => (string)$response->getBody(),
                    "response" => $response,
                ];
                $this->frontendCache->setWithTags($cacheKey, $entry, [static::CACHE_TAG], 24 * 60 * 60);
            }

            // Set cache status
            if ($searchInServerCache && $writeServerCache) {
                $cacheStatus = "new";
            } elseif (! $searchInServerCache && $writeServerCache) {
                $cacheStatus = "force-update";
            } else {
                $cacheStatus = "no-cache";
            }
            $response = $response->withHeader("T3fa-Server-Cache-Status", $cacheStatus);
        }

        // Set browser caches
        if ($searchInServerCache && $writeServerCache && $writeBrowserCache) {
            // Browser cache enabled
            $browserCacheTtl = $writeServerCache ? $this->configRepository->site()->getCurrentSiteConfig()->browserCacheTtl : -1;
            $response        = $response->withHeader("Last-Modified", $response->getHeaderLine("T3fa-Generated"));
            $response        = $response->withHeader("Expires", gmdate("D, d M Y H:i:s \G\M\T", time() + $browserCacheTtl));
            if (! $response->hasHeader("Cache-Control")) {
                $response = $response->withHeader("Cache-Control", "max-age=" . $browserCacheTtl);
            }
        } else {
            // Browser cache disabled
            $response = $response->withHeader("Last-Modified", gmdate("D, d M Y H:i:s \G\M\T"));
            $response = $response->withHeader("Cache-Control", "no-cache, no-store, max-age=0, must-revalidate");
            $response = $response->withHeader("Pragma", "no-cache");
        }

        // Set cache status
        $response = $response->withHeader("T3fa-Browser-Cache-Enabled", $writeBrowserCache ? "yes" : "no");

        // Done
        return $response;
    }

    /**
     * Internal helper to build the cache key that incorporates the important details for a single cache id
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return string
     */
    protected function makeCacheKey(ServerRequestInterface $request): string
    {
        $cacheKey = Arrays::flatten($request->getQueryParams());
        ksort($cacheKey);
        $cacheKey = implode("-", array_keys($cacheKey)) . "-" .
                    implode("-", $cacheKey) . "-" .
                    $request->getUri()->getPath() . "-" .
                    $this->context->getLanguageAspect()->getCurrentFrontendLanguage()->getTwoLetterIsoCode() . "-" .
                    $this->context->getPidAspect()->getCurrentPid() . "-" .
                    $this->configRepository->site()->getSite()->getIdentifier();
        if ($this->context->getBeUserAspect()->isLoggedIn()) {
            $cacheKey .= "-be-" . $this->context->getBeUserAspect()->getUser()->user["uid"];
        }
        if ($this->context->getFeUserAspect()->isLoggedIn()) {
            $cacheKey .= "-fe-" . $this->context->getFeUserAspect()->getUser()->user["uid"];
        }

        return static::CACHE_TAG . "-" . trim($cacheKey, "-");
    }
}
