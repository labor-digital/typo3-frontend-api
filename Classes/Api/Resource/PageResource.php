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
 * Last modified: 2021.06.10 at 12:47
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3fa\Api\Resource\Entity\PageEntity;
use LaborDigital\T3fa\Api\Resource\Factory\Page\PageResourceFactory;
use LaborDigital\T3fa\Core\Resource\AbstractResource;
use LaborDigital\T3fa\Core\Resource\Exception\InvalidIdException;
use LaborDigital\T3fa\Core\Resource\Exception\NoCollectionException;
use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\ExtConfigHandler\Api\Resource\ResourceConfigurator;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;

class PageResource extends AbstractResource
{
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\PageResourceFactory
     */
    protected $factory;
    
    public function __construct(PageResourceFactory $factory)
    {
        $this->factory = $factory;
    }
    
    /**
     * @inheritDoc
     */
    public static function configure(ResourceConfigurator $configurator, SiteConfigContext $context): void
    {
        $configurator->registerClass(PageEntity::class);
    }
    
    /**
     * @inheritDoc
     * @noinspection DuplicatedCode
     */
    public function findSingle($id, ResourceContext $context)
    {
        if (! is_numeric($id) && $id !== 'current') {
            throw new InvalidIdException();
        }
        
        $typoContext = $context->getTypoContext();
        
        if ($id === 'current') {
            $id = $typoContext->pid()->getCurrent();
        }
        
        try {
            return $this->factory->make(
                (int)$id,
                $typoContext->language()->getCurrentFrontendLanguage(),
                $typoContext->site()->getCurrent());
        } catch (PageNotFoundException $exception) {
            throw new ResourceNotFoundException('There is no page with the given id: ' . $id);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function findCollection(ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        throw new NoCollectionException($context->getResourceType());
    }
    
}