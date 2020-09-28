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
 * Last modified: 2020.09.27 at 20:26
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ApiRouter\Traits;


use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use League\Route\Route;

trait RouteStrategyTrait
{
    use FrontendApiContextAwareTrait;
    use ResponseFactoryTrait;


    /**
     * Finds the route configuration object for a given router-route.
     *
     * @param   \League\Route\Route  $route
     *
     * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
     */
    protected function getRouteConfig(Route $route): RouteConfig
    {
        return $this->FrontendApiContext()->ConfigRepository()->routing()->getRouteConfig($route);
    }

    /**
     * Announces the route's cache options to the cache middleware
     *
     * @param   \League\Route\Route  $route
     */
    protected function announceRouteCacheOptions(Route $route): void
    {
        $this->FrontendApiContext()->CacheService()->announceCacheOptions(
            $this->getRouteConfig($route)->getCacheOptionsArray()
        );
    }
}
