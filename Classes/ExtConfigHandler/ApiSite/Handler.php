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
 * Last modified: 2021.06.02 at 20:29
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigHandler;
use LaborDigital\T3ba\ExtConfig\ExtConfigService;
use LaborDigital\T3ba\ExtConfig\Interfaces\SiteBasedHandlerInterface;
use LaborDigital\T3fa\Api\Bundle\CategoryBundle;
use LaborDigital\T3fa\Api\Bundle\ContentBundle;
use LaborDigital\T3fa\Api\Bundle\FileBundle;
use LaborDigital\T3fa\Api\Bundle\PageBundle;
use LaborDigital\T3fa\Api\Bundle\ValueTransformerBundle;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\PageConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceCollector;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Routing\RoutingConfigurator;
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
            ContentBundle::class => [],
        ];
    
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\ApiSite\ApiSiteConfigurator
     */
    protected $configurator;
    
    /**
     * @var \LaborDigital\T3ba\ExtConfig\ExtConfigService
     */
    protected $extConfigService;
    
    public function __construct(ExtConfigService $extConfigService)
    {
        $this->extConfigService = $extConfigService;
    }
    
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
                $this->getInstanceWithoutDi(RoutingConfigurator::class),
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
        /** @noinspection PhpUndefinedMethodInspection */
        $class::registerBundles($bundleCollector);
        
        // Register resources
        $resourceCollector = $this->getInstanceWithoutDi(ResourceCollector::class, [$context->getSiteKey()]);
        foreach ($bundleCollector->getAll() as $bundleClass) {
            $this->runBundleInNamespace($bundleClass, function () use ($bundleClass, $resourceCollector, $context, $bundleCollector) {
                $bundleClass::registerResources(
                    $resourceCollector,
                    $context,
                    $bundleCollector->getOptions($bundleClass)
                );
            });
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $class::registerResources($resourceCollector, $context);
        $this->handleResources($resourceCollector);
        
        // Execute configureSite methods
        foreach ($bundleCollector->getAll() as $bundleClass) {
            $this->runBundleInNamespace($bundleClass, function () use ($bundleClass, $context, $bundleCollector) {
                $bundleClass::configureSite(
                    $this->configurator,
                    $context,
                    $bundleCollector->getOptions($bundleClass)
                );
            });
            
            $bundleClass::configureSite($this->configurator, $context, $bundleCollector->getOptions($bundleClass));
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $class::configureSite($this->configurator, $context);
        
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
            
            $class::configure($configurator, $context);
            
            $configurator->finish($this->configurator, $types, $classMap);
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
        $state->useNamespace('t3fa.routing', [$this->configurator->routing(), 'finish']);
        
        $state->useNamespace('t3fa.site', [$this->configurator, 'finish']);
    }
    
    /**
     * Internal helper to run the given callback in the namespace context for the bundle class
     *
     * @param   string    $bundleClass
     * @param   callable  $callback
     *
     * @return \LaborDigital\T3ba\ExtConfig\ExtConfigContext|\Neunerlei\Configuration\Loader\ConfigContext
     */
    protected function runBundleInNamespace(string $bundleClass, callable $callback)
    {
        $namespace = $this->extConfigService->resolveNamespaceForClass($bundleClass);
        
        if ($namespace === null) {
            return $callback();
        }
        
        return $this->context->runWithNamespace($namespace, $callback);
    }
    
}