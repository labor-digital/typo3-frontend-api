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
 * Last modified: 2021.05.20 at 17:53
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigHandler;
use LaborDigital\T3ba\ExtConfig\Interfaces\SiteBasedHandlerInterface;
use LaborDigital\T3fa\ApiSite\Bundle\CategoryBundle;
use LaborDigital\T3fa\ApiSite\Bundle\FileBundle;
use LaborDigital\T3fa\ApiSite\Bundle\PageBundle;
use LaborDigital\T3fa\ApiSite\Bundle\ValueTransformerBundle;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\PageConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceCollector;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Transformer\TransformerConfigurator;
use Neunerlei\Configuration\Handler\HandlerConfigurator;

class Handler extends AbstractExtConfigHandler implements SiteBasedHandlerInterface
{
    public const DEFAULT_BUNDLES
        = [
            ValueTransformerBundle::class => [],
            PageBundle::class => [],
            CategoryBundle::class => [],
            FileBundle::class => [],
        ];
    
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\ApiSite\ApiSiteConfigurator
     */
    protected $configurator;
    
    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
        $configurator->registerInterface(ConfigureApiSiteInterface::class);
        $configurator->registerDefaultConfigClass(DefaultApiSiteConfig::class);
        $this->registerDefaultLocation($configurator);
    }
    
    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $this->configurator = $this->getInstanceWithoutDi(
            ApiSiteConfigurator::class,
            [
                $this->getInstanceWithoutDi(TransformerConfigurator::class),
                $this->getInstanceWithoutDi(PageConfigurator::class),
            ]
        );
    }
    
    /**
     * @inheritDoc
     */
    public function handle(string $class): void
    {
        /** @var \LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext $context */
        $context = $this->context;
        
        /** @var \LaborDigital\T3fa\ExtConfigHandler\ApiSite\BundleCollector $bundleCollector */
        $bundleCollector = $this->getInstanceWithoutDi(BundleCollector::class, [static::DEFAULT_BUNDLES]);
        call_user_func([$class, 'registerBundles'], $bundleCollector);
        
        // Register resources
        $resourceCollector = $this->getInstanceWithoutDi(ResourceCollector::class, [$context->getSiteKey()]);
        foreach ($bundleCollector->getAll() as $bundleClass) {
            call_user_func([$bundleClass, 'registerResources'], $resourceCollector, $context, $bundleCollector->getOptions($bundleClass));
        }
        call_user_func([$class, 'registerResources'], $resourceCollector, $context);
        $this->handleResources($resourceCollector);
        
        // Execute configureSite methods
        foreach ($bundleCollector->getAll() as $bundleClass) {
            call_user_func([$bundleClass, 'configureSite'], $this->configurator, $context, $bundleCollector->getOptions($bundleClass));
        }
        call_user_func([$class, 'configureSite'], $this->configurator, $context);
        
    }
    
    /**
     * Handles the configuration of all registered resource classes
     *
     * @param   \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceCollector  $collector
     */
    protected function handleResources(ResourceCollector $collector): void
    {
        /** @var \LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext $context */
        $context = $this->context;
        
        $types = [];
        $classMap = [];
        
        foreach ($collector->getAll() as $resourceType) {
            ['class' => $class, 'options' => $options] = $collector->get($resourceType);
            
            /** @var ResourceConfigurator $configurator */
            $configurator = $this->getInstanceWithoutDi(ResourceConfigurator::class, [
                $resourceType,
                $class,
                $options,
            ]);
            
            call_user_func([$class, 'configure'], $configurator, $context);
            
            $configurator->finish($this->configurator->transformer(), $types, $classMap);
        }
        
        $context->getState()
                ->set('t3fa.resource.types', $types)
                ->set('t3fa.resource.classMap', $classMap);
    }
    
    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        /** @var \LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext $context */
        $context = $this->context;
        $state = $context->getState();
        
        $state->useNamespace('t3fa.transformer', [$this->configurator->transformer(), 'finish']);
        $state->useNamespace('t3fa.page', [$this->configurator->page(), 'finish']);
    }
    
}