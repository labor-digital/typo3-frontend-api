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
 * Last modified: 2019.08.06 at 14:18
 */

namespace LaborDigital\Typo3FrontendApi\ExtConfig;


use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractExtConfigOption;
use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiMiddlewareOption;
use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiRoutingOption;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption;
use LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\FrontendApiResourceOption;
use LaborDigital\Typo3FrontendApi\Site\Configuration\FrontendApiSiteOption;

/**
 * Class FrontendApiOption
 *
 * Provides the configuration options for all features of the frontend api
 *
 * @package LaborDigital\Typo3FrontendApi\ExtConfig
 */
class FrontendApiOption extends AbstractExtConfigOption
{
    /**
     * FrontendApiOption constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(FrontendApiConfigRepository $configRepository)
    {
        $configRepository->__setConfigResolver(function () {
            return $this->getCachedValueOrRun("configuration", function () {
                // Build the config for all children
                $config = [];
                $this->middleware()->__buildConfig($config);
                $this->resource()->__buildConfig($config);
                $this->routing()->__buildConfig($config);
                $this->hybrid()->__buildConfig($config);
                $this->contentElement()->__buildConfig($config);
                $this->site()->__buildConfig($config);
                $this->tool()->__buildConfig($config);
                $this->cache()->__buildConfig($config);

                return $config;

            });
        });
    }

    /**
     * Contains the routing specific configuration options.
     *
     * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiRoutingOption
     */
    public function routing(): FrontendApiRoutingOption
    {
        return $this->getChildOptionInstance(FrontendApiRoutingOption::class);
    }

    /**
     * Contains the resource specific configuration options.
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\FrontendApiResourceOption
     */
    public function resource(): FrontendApiResourceOption
    {
        return $this->getChildOptionInstance(FrontendApiResourceOption::class);
    }

    /**
     * Contains the middleware specific configuration options.
     *
     * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\FrontendApiMiddlewareOption
     */
    public function middleware(): FrontendApiMiddlewareOption
    {
        return $this->getChildOptionInstance(FrontendApiMiddlewareOption::class);
    }

    /**
     * Contains the content element specific configuration options.
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     */
    public function contentElement(): FrontendApiContentElementOption
    {
        return $this->getChildOptionInstance(FrontendApiContentElementOption::class);
    }

    /**
     * Contains the site specific configuration options.
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\FrontendApiSiteOption
     */
    public function site(): FrontendApiSiteOption
    {
        return $this->getChildOptionInstance(FrontendApiSiteOption::class);
    }

    /**
     * Contains the hybrid app specific configuration options.
     *
     * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
     */
    public function hybrid(): FrontendApiHybridAppOption
    {
        return $this->getChildOptionInstance(FrontendApiHybridAppOption::class);
    }

    /**
     * Contains generic, tool specific configuration options.
     *
     * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiToolOption
     */
    public function tool(): FrontendApiToolOption
    {
        return $this->getChildOptionInstance(FrontendApiToolOption::class);
    }

    /**
     * Caching related configuration, like the default ttl or the cache identifier
     *
     * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiCacheOption
     */
    public function cache(): FrontendApiCacheOption
    {
        return $this->getChildOptionInstance(FrontendApiCacheOption::class);
    }
}
