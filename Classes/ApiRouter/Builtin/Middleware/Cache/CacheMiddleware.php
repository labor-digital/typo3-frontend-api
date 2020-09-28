<?php
/*
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
 * Last modified: 2020.09.23 at 22:00
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\Cache;


use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\Cache\CacheService;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\RequestCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function GuzzleHttp\Psr7\stream_for;

class CacheMiddleware implements MiddlewareInterface
{
    public const CACHE_STATUS_HEADER    = 'X-T3FA-Server-Cache-Status';
    public const CACHE_GENERATED_HEADER = 'X-T3FA-Generated';

    /**
     * If the response contains this header, the middleware will not send any browser cache related headers
     */
    public const RESPONSE_IGNORE_BROWSER_CACHE_HEADER = 'X-T3FA-Cache-Ignore-Browser-Cache';

    /**
     * If the response contains this header, it will not be cached by this middleware
     */
    public const RESPONSE_NO_CACHE_HEADER = 'X-T3FA-Cache-No-Cache';

    /**
     * If the response contains this header, the cache ttl will be set to the given value in seconds
     */
    public const RESPONSE_CACHE_TTL_HEADER = 'X-T3FA-Cache-TTL';

    /**
     * @var \LaborDigital\Typo3FrontendApi\Cache\CacheService
     */
    protected $cacheService;

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $typoContext;

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * CacheMiddleware constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\Cache\CacheService                     $cacheService
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext                  $typoContext
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(CacheService $cacheService, TypoContext $typoContext, FrontendApiConfigRepository $configRepository)
    {
        $this->cacheService     = $cacheService;
        $this->typoContext      = $typoContext;
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isCacheable = in_array(strtoupper($request->getMethod()), ['GET', 'HEAD']);
        $this->cacheService->setUpdate(
            $this->typoContext->BeUser()->isLoggedIn() && (
                stripos($request->getHeaderLine('cache-control'), 'no-cache') !== false
                || stripos($request->getHeaderLine('pragma'), 'no-cache') !== false
            )
        );

        // Make sure to remove typos cache headers to ensure we have a blank slate to work with
        foreach (['Expires', 'Last-Modified', 'Cache-Control', 'Pragma'] as $key) {
            header_remove($key);
        }

        // Read the response from cache or continue in the middleware stack
        /** @var ResponseInterface $response */
        $response = $this->cacheService->remember(static function () use ($request, $handler) {
            $response = $handler->handle($request);
            $response = $response->withHeader(static::CACHE_STATUS_HEADER, 'new');
            $response = $response->withHeader(static::CACHE_GENERATED_HEADER, gmdate("D, d M Y H:i:s \G\M\T"));

            return $response;
        }, [
            'keyGenerator' => new RequestCacheKeyGenerator($request),
            'enabled'      => static function (ResponseInterface $response) use (&$isCacheable) {
                return $isCacheable = $isCacheable
                                      && in_array($response->getStatusCode(), [200, 203, 204, 206], true)
                                      && ! $response->hasHeader(static::RESPONSE_NO_CACHE_HEADER);
            },
            'ttl'          => static function (ResponseInterface $response) {
                if ($response->hasHeader(static::RESPONSE_CACHE_TTL_HEADER)) {
                    $ttl = $response->getHeaderLine(static::RESPONSE_CACHE_TTL_HEADER);
                    if (is_numeric($ttl)) {
                        return (int)$ttl;
                    }
                }

                return null;
            },
            'onFreeze'     => static function (ResponseInterface $response): array {
                return [
                    'body'     => (string)$response->getBody(),
                    'response' => $response,
                ];
            },
            'onWarmup'     => static function (array $data): ResponseInterface {
                $response = $data['response'];
                $response = $response->withBody(stream_for($data['body']));
                $response = $response->withHeader(static::CACHE_STATUS_HEADER, 'hit');

                return $response;
            },
        ]);

        // Post-Process non cacheable responses
        if (! $isCacheable) {
            $response = $response->withHeader(static::CACHE_STATUS_HEADER, 'no-cache');
        }

        // Disable browser caching for API results
        if (! $response->hasHeader(static::RESPONSE_IGNORE_BROWSER_CACHE_HEADER)) {
            $response = $response->withHeader('Last-Modified', gmdate("D, d M Y H:i:s \G\M\T"));
            $response = $response->withHeader('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response = $response->withHeader('Pragma', 'no-cache');
        }

        // Remove internal headers
        $response = $response->withoutHeader(static::RESPONSE_CACHE_TTL_HEADER);
        $response = $response->withoutHeader(static::RESPONSE_IGNORE_BROWSER_CACHE_HEADER);
        $response = $response->withoutHeader(static::RESPONSE_NO_CACHE_HEADER);

        return $response;
    }

}
