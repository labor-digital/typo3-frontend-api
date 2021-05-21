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
 * Last modified: 2020.09.27 at 20:29
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Strategy;


use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\RouteStrategyTrait;
use League\Route\Route;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExtendedApplicationStrategy extends ApplicationStrategy
{
    use RouteStrategyTrait;

    /**
     * @inheritDoc
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $this->announceRouteCacheOptions($route);

        return parent::invokeRouteCallable($route, $request);
    }

}
