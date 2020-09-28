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
 * Last modified: 2019.08.26 at 21:29
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Traits;


use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\CacheHandler\CacheMiddleware;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait CacheControllingStrategyTrait
 *
 * @package    LaborDigital\Typo3FrontendApi\ApiRouter\Traits
 * @deprecated will be removed in v10 use RouteStrategyTrait instead!
 */
trait CacheControllingStrategyTrait
{
    use RouteConfigAwareTrait;


    /**
     * This method checks if a given route has a configured "useCache" attribute that is set to false.
     * If so it will add an X-FRONTEND-API-CACHE header to the response with the value of false
     *
     * @param   \League\Route\Route                  $route
     * @param   \Psr\Http\Message\ResponseInterface  $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @deprecated will be removed in v10 use RouteStrategyTrait instead!
     */
    protected function addInternalNoCacheHeaderIfRequired(Route $route, ResponseInterface $response): ResponseInterface
    {
        $routeConfig = $this->getRouteConfig($route);
        if (! $routeConfig->isUseCache()) {
            return $response->withAddedHeader(CacheMiddleware::CACHE_CONTROL_HEADER, "off");
        }

        return $response;
    }
}
