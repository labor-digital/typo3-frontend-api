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
 * Last modified: 2021.06.11 at 14:28
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Middleware\Api;


use GuzzleHttp\Psr7\Utils;
use LaborDigital\T3ba\Tool\Cache\KeyGenerator\RequestCacheKeyGenerator;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Core\Cache\Implementation\T3faCache;
use LaborDigital\T3fa\Core\Cache\Metrics\MetricsRenderer;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CacheMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use TypoContextAwareTrait;
    use LoggerAwareTrait;
    use T3faCacheAwareTrait;
    
    /**
     * Contains the information if the response was cached or not
     */
    public const CACHE_STATUS_HEADER = 'X-t3fa-Server-Cache-Status';
    
    /**
     * Contains the timestamp when the response was generated
     */
    public const CACHE_GENERATED_HEADER = 'X-t3fa-Generated';
    
    /**
     * If the response contains this header, the middleware will not send any browser cache related headers
     */
    public const RESPONSE_IGNORE_BROWSER_CACHE_HEADER = 'X-t3fa-Cache-Ignore-Browser-Cache';
    
    /**
     * If the response contains this header, it will not be cached by this middleware
     */
    public const RESPONSE_NO_CACHE_HEADER = 'X-t3fa-Cache-No-Cache';
    
    /**
     * If this parameter is present in the get parameters of a request
     * the middleware will render a list of cache metrics for debugging purposes.
     * With the metrics you can see exactly how long each caching step took and what it was tagged with.
     */
    public const REQUEST_QUERY_METRICS_ARG = 'renderCacheMetrics';
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cacheOptions = $request->getAttribute('staticRouteAttributes', [])['@cacheOptions'] ?? [];
        
        $env = $this->getTypoContext()->env();
        $renderMetrics = isset($request->getQueryParams()[static::REQUEST_QUERY_METRICS_ARG])
                         && ($env->isFeDebug() || $env->isDev());
        
        $isCacheable = in_array(strtoupper($request->getMethod()), ['GET', 'HEAD']);
        if (! $cacheOptions['enabled']) {
            $isCacheable = false;
        }
        
        // Make sure to remove TYPO3s cache headers to ensure we have a blank slate to work with
        foreach (['Expires', 'Last-Modified', 'Cache-Control', 'Pragma'] as $key) {
            header_remove($key);
        }
        
        $cache = $this->getCache();
        
        if (! $isCacheable && $cache instanceof T3faCache) {
            $cache->setGloballyDisabled();
        }
        
        $response = $cache->remember(static function () use ($request, $handler): ResponseInterface {
            $response = $handler->handle($request);
            $response = $response->withHeader(static::CACHE_STATUS_HEADER, 'new');
            
            return $response->withHeader(static::CACHE_GENERATED_HEADER, gmdate("D, d M Y H:i:s \G\M\T"));
            
        }, null, array_merge(
            $cacheOptions,
            [
                'keyGenerator' => new RequestCacheKeyGenerator($request),
                // I limit the lifetime of the cache to a day here,
                // because the request cache handles a lot of possible permutations.
                // This allows the garbage collector to flush the cache more frequently.
                // @todo a config option would be nice here
                'lifetime' => 60 * 60 * 24,
                'enabled' => static function (ResponseInterface $response) use (&$isCacheable) {
                    $isCacheable = $isCacheable && in_array($response->getStatusCode(), [200, 203, 204, 206], true);
                    
                    return $isCacheable && ! $response->hasHeader(static::RESPONSE_NO_CACHE_HEADER);
                },
                'onFreeze' => static function (ResponseInterface $response): array {
                    return [
                        'body' => (string)$response->getBody(),
                        'response' => $response,
                    ];
                },
                'onWarmup' => static function (array $data): ResponseInterface {
                    $response = $data['response'];
                    $response = $response->withBody(Utils::streamFor($data['body']));
                    $response = $response->withHeader(static::CACHE_STATUS_HEADER, 'hit');
                    
                    return $response;
                },
            ]
        ));
        
        if ($renderMetrics) {
            $metrics = $this->getTypoContext()->di()->getService(MetricsRenderer::class)->render();
            $response = $response->withBody(Utils::streamFor($metrics));
            $response = $response->withHeader('Content-Type', 'text/plain');
            $response = $response->withHeader(static::CACHE_STATUS_HEADER, 'metrics');
            $response = $response->withHeader('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response = $response->withHeader('Pragma', 'no-cache');
            $this->logger->debug('Rendering cache metrics: ' . $request->getUri());
            
            return $response;
        }
        
        if (! $isCacheable) {
            $response = $response->withHeader(static::CACHE_STATUS_HEADER, 'no-cache');
        }
        
        if (! $response->hasHeader(static::RESPONSE_IGNORE_BROWSER_CACHE_HEADER)) {
            $response = $response->withHeader('Last-Modified', gmdate("D, d M Y H:i:s \G\M\T"));
            $response = $response->withHeader('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response = $response->withHeader('Pragma', 'no-cache');
        }
        
        $response = $response->withoutHeader(static::RESPONSE_IGNORE_BROWSER_CACHE_HEADER);
        $response = $response->withoutHeader(static::RESPONSE_NO_CACHE_HEADER);
        
        $this->logger->debug(
            'Response caching: ' . $request->getUri() . ' | T3FA: ' .
            $response->getHeaderLine(static::CACHE_STATUS_HEADER) . ', ' .
            $response->getHeaderLine(static::CACHE_GENERATED_HEADER) .
            ' | Browser: ' . $response->getHeaderLine('Cache-Control') . ', ' .
            $response->getHeaderLine('Pragma'));
        
        return $response;
    }
}