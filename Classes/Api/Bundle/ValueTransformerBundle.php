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
 * Last modified: 2021.06.02 at 20:35
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Bundle;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3fa\Api\Resource\Transformer\DateTransformer;
use LaborDigital\T3fa\Api\Resource\Transformer\TypoLinkTransformer;
use LaborDigital\T3fa\ExtConfigHandler\Api\ApiBundleInterface;
use LaborDigital\T3fa\ExtConfigHandler\Api\ApiConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\Api\Resource\ResourceCollector;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class ValueTransformerBundle implements ApiBundleInterface
{
    /**
     * @inheritDoc
     */
    public static function registerResources(ResourceCollector $collector, SiteConfigContext $context, array $options): void { }
    
    /**
     * @inheritDoc
     */
    public static function configureSite(ApiConfigurator $configurator, SiteConfigContext $context, array $options): void
    {
        $configurator->transformer()
                     ->registerValueTransformer(DateTransformer::class, \DateTime::class)
                     ->registerValueTransformer(TypoLinkTransformer::class, [
                         Link::class,
                         UriInterface::class,
                         \League\Uri\Contracts\UriInterface::class,
                         UriBuilder::class,
                     ]);
    }
    
}