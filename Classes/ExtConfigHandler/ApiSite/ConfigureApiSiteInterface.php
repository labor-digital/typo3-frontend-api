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
 * Last modified: 2021.06.02 at 20:22
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceCollector;

interface ConfigureApiSiteInterface
{
    /**
     * Bundles can be provided by extensions to configure the frontend API site to match their needs.
     *
     * The bundles will be loaded before the registerResources / configureSite methods are executed.
     * This allows you to modify every change provided by a bundle yourself.
     *
     * If your site does not need any bundles, simply ignore this method.
     *
     * @see \LaborDigital\T3fa\ExtConfigHandler\ApiSite\ApiBundleInterface
     */
    public static function registerBundles(BundleCollector $collector): void;
    
    /**
     * Resource classes are used to translate TYPO3 data into an api ready resource.
     * The source class is basically an abstract repository/mapper to convert an api request into a db query, further api request
     * or static data map. Each resource also has a mapped route to it which follows the json:api standard.
     *
     * Resource classes can overlap with the actual site configuration and are therefore processed before the configureSite() method is executed.
     * This allows you to modify every change provided by a resource in the configureSite() method.
     *
     * This method is executed after the registerResources() of all registered bundles, which allows you to override resources before they are applied
     * into the site configuration.
     *
     * @param   \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceCollector  $collector
     * @param   \LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext                $context
     */
    public static function registerResources(ResourceCollector $collector, SiteConfigContext $context): void;
    
    /**
     * Configures a single site for the frontend api. The options range from routing to resource handling.
     *
     * @param   \LaborDigital\T3fa\ExtConfigHandler\ApiSite\ApiSiteConfigurator  $configurator
     * @param   \LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext         $context
     */
    public static function configureSite(ApiSiteConfigurator $configurator, SiteConfigContext $context): void;
}