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
 * Last modified: 2021.05.20 at 17:54
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ApiSite\Bundle;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\ApiSiteBundleInterface;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\ApiSiteConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceCollector;
use LaborDigital\T3fa\Resource\File;
use LaborDigital\T3fa\Resource\FileReference;

class FileBundle implements ApiSiteBundleInterface
{
    /**
     * @inheritDoc
     */
    public static function registerResources(ResourceCollector $collector, SiteConfigContext $context, array $options): void
    {
        $collector->register(File::class);
        $collector->register(FileReference::class);
    }
    
    /**
     * @inheritDoc
     */
    public static function configureSite(ApiSiteConfigurator $configurator, SiteConfigContext $context, array $options): void { }
    
}