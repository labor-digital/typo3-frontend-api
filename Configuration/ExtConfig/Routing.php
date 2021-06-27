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
 * Last modified: 2021.06.23 at 18:17
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Configuration\ExtConfig;


use LaborDigital\T3ba\ExtConfig\ExtConfigContext;
use LaborDigital\T3ba\ExtConfigHandler\Routing\ConfigureRoutingInterface;
use LaborDigital\T3ba\ExtConfigHandler\Routing\RoutingConfigurator;
use LaborDigital\T3fa\Middleware\Typo\ApiForkMiddleware;
use LaborDigital\T3fa\Middleware\Typo\ImagingMiddleware;

class Routing implements ConfigureRoutingInterface
{
    /**
     * @inheritDoc
     */
    public static function configureRouting(RoutingConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->registerMiddleware(ApiForkMiddleware::class, [
            'after' => [
                'typo3/cms-frontend/eid',
            ],
            'before' => 'typo3/cms-frontend/site',
        ]);
        $configurator->registerMiddleware(ImagingMiddleware::class, [
            'before' => 'typo3/cms-frontend/timetracker',
        ]);
    }
}