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
 * Last modified: 2019.08.06 at 13:57
 */

namespace LaborDigital\Typo3FrontendApi\ExtConfig;


use DateTime;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\Extension\ExtConfigExtensionInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\Extension\ExtConfigExtensionRegistry;
use LaborDigital\Typo3BetterApi\ExtConfig\OptionList\ExtConfigOptionList;
use LaborDigital\Typo3BetterApi\Link\TypoLink;
use LaborDigital\Typo3BetterApi\Middleware\RequestCollectorMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Controller\SchedulerController;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Controller\UpController;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\BodyParser\BodyParserMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\CacheHandler\CacheMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\CacheHandler\CacheMiddlewareEventHandler;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\ErrorHandler\ErrorHandlerMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\FrontendSimulation\FrontendSimulationMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\RedirectHandler\ApiRedirectMiddleware;
use LaborDigital\Typo3FrontendApi\ApiRouter\ResponseFactory;
use LaborDigital\Typo3FrontendApi\ContentElement\BackendListLabelFilterEventHandler;
use LaborDigital\Typo3FrontendApi\Domain\Table\Override\TtContentOverrides;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingEventHandler;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer\DefaultSpecialObjectTransformer;
use LaborDigital\Typo3FrontendApi\TypoMiddleware\ApiMiddlewareFork;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;

class Typo3FrontendApiExtConfig implements ExtConfigInterface, ExtConfigExtensionInterface
{
    /**
     * @inheritDoc
     */
    public function configure(ExtConfigOptionList $configurator, ExtConfigContext $context)
    {
        // Register translation
        $configurator->translation()->registerContext('frontendApi');

        // Register new implementations
        $configurator->core()
                     ->registerImplementation(ResponseFactoryInterface::class, ResponseFactory::class);

        // Register table changes
        $configurator->table()->registerTableOverride(TtContentOverrides::class, "tt_content");

        // Register our event handlers
        $configurator->event()
                     ->registerLazySubscriber(CacheMiddlewareEventHandler::class)
                     ->registerLazySubscriber(ImagingEventHandler::class);

        // Register default middlewares
        $frontendApi = $configurator->frontendApi();
        $frontendApi
            ->middleware()
            ->registerGlobalMiddleware(ErrorHandlerMiddleware::class, ["middlewareStack" => "external"])
            ->registerGlobalMiddleware(BodyParserMiddleware::class, ['middlewareStack' => 'external'])
            ->registerGlobalMiddleware(ApiRedirectMiddleware::class,
                ["middlewareStack" => "external", "before" => FrontendSimulationMiddleware::class])
            ->registerGlobalMiddleware(FrontendSimulationMiddleware::class)
            ->registerGlobalMiddleware(CacheMiddleware::class, ["middlewareStack" => "external"]);

        // Register the default resources
        $frontendApi->resource()->registerResourcesDirectory("EXT:{{extkey}}/Classes/JsonApi/Builtin/Resource");

        // Register default special object transformations
        $frontendApi->resource()->registerSpecialObjectTransformer(DefaultSpecialObjectTransformer::class,
            [DateTime::class, TypoLink::class, UriInterface::class, UriBuilder::class]);

        // Register default routes
        $frontendApi->routing()
                    ->registerRouteController(UpController::class)
                    ->registerRouteController(SchedulerController::class);

        // Register typo middleware
        $configurator->http()->registerMiddleware(ApiMiddlewareFork::class, "frontend", [
            "after"  => [
                RequestCollectorMiddleware::class,
                "typo3/cms-frontend/base-redirect-resolver",
            ],
            "before" => "typo3/cms-frontend/static-route-resolver",
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function extendExtConfig(ExtConfigExtensionRegistry $extender, ExtConfigContext $context)
    {
        $extender->registerOptionListEntry(FrontendApiOption::class);
    }

}
