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
 * Last modified: 2021.06.02 at 20:26
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Api\Resource\Entity\PageEntity;
use LaborDigital\T3fa\Api\Resource\Factory\Page\PageResourceFactory;
use LaborDigital\T3fa\Api\Resource\PostProcessor\TestPostProcessor;
use LaborDigital\T3fa\Api\Resource\Transformer\PageTransformer;
use LaborDigital\T3fa\Core\Resource\AbstractResource;
use LaborDigital\T3fa\Core\Resource\Exception\InvalidIdException;
use LaborDigital\T3fa\Core\Resource\Exception\NoCollectionException;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceConfigurator;

class PageResource extends AbstractResource
{
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $context;
    
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\PageResourceFactory
     */
    protected $factory;
    
    public function __construct(TypoContext $context, PageResourceFactory $factory)
    {
        $this->context = $context;
        $this->factory = $factory;
    }
    
    /**
     * @inheritDoc
     */
    public static function configure(ResourceConfigurator $configurator, SiteConfigContext $context): void
    {
        $configurator->registerClass(PageEntity::class);
        $configurator->registerTransformer(PageTransformer::class);
        $configurator->registerPostProcessor(TestPostProcessor::class);
        
        $configurator->setIsCacheEnabled(false);
    }
    
    /**
     * @inheritDoc
     */
    public function findSingle($id, ResourceContext $context)
    {
        if (! is_numeric($id) && $id !== 'current') {
            throw new InvalidIdException();
        }
        
        if ($id === 'current') {
            $id = $this->context->pid()->getCurrent();
        }
        
        return $this->factory->make(
            (int)$id,
            $this->context->language()->getCurrentFrontendLanguage(),
            $this->context->site()->getCurrent());
    }
    
    /**
     * @inheritDoc
     */
    public function findCollection(ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        throw new NoCollectionException($context->getResourceType());
    }
    
}