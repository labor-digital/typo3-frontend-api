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

use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceCollector;

/**
 * Interface ApiBundleInterface
 *
 * Allows extensions to provide a default configuration that can be added
 * to a site using the ApiSiteConfigurator by the implementing developer.
 *
 * @package LaborDigital\T3fa\ExtConfigHandler\ApiSite
 */
interface ApiBundleInterface extends NoDiInterface
{
    /**
     * Can be used to register new resources provided by this bundle
     *
     * @param   ResourceCollector  $collector  The resource collector to register the resource classes with
     * @param   SiteConfigContext  $context    Additional context information about the site to configure
     * @param   array              $options    Optional options that can be provided in the registerBundles() method
     */
    public static function registerResources(ResourceCollector $collector, SiteConfigContext $context, array $options): void;
    
    /**
     * MUST apply the bundle configuration to the site configurator
     *
     * @param   ApiSiteConfigurator  $configurator  The configurator to add the setup to
     * @param   SiteConfigContext    $context       Additional context information about the site to configure
     * @param   array                $options       Optional options that can be provided in the registerBundles() method
     *
     * @see ConfigureApiSiteInterface::registerBundles() on how to register a bundle
     */
    public static function configureSite(ApiSiteConfigurator $configurator, SiteConfigContext $context, array $options): void;
    
}