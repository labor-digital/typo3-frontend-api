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
 * Last modified: 2021.05.21 at 19:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Resource;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\Page\PageService;
use LaborDigital\T3fa\Core\Resource\AbstractResource;
use LaborDigital\T3fa\Core\Resource\Exception\InvalidQueryException;
use LaborDigital\T3fa\Core\Resource\Exception\NoCollectionException;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\Event\Resource\Page\PageRootLineFilterEvent;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\RootLineDataProviderInterface;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceConfigurator;
use LaborDigital\T3fa\Resource\Entity\PageRootLineEntity;
use Neunerlei\Inflection\Inflector;
use Psr\EventDispatcher\EventDispatcherInterface;

class PageRootLine extends AbstractResource
{
    /**
     * @var \LaborDigital\T3ba\Tool\Page\PageService
     */
    protected $pageService;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    public function __construct(
        PageService $pageService,
        EventDispatcherInterface $eventDispatcher,
        LinkService $linkService
    )
    {
        $this->pageService = $pageService;
        $this->eventDispatcher = $eventDispatcher;
        $this->linkService = $linkService;
    }
    
    /**
     * @inheritDoc
     */
    public static function configure(ResourceConfigurator $configurator, SiteConfigContext $context): void
    {
        $configurator->registerClass(PageRootLineEntity::class);
    }
    
    /**
     * @inheritDoc
     */
    public function findSingle($id, ResourceContext $context)
    {
        if (! is_numeric($id)) {
            throw new InvalidQueryException('The id to request a root line must be an integer');
        }
        
        return $this->findRootLine($id, $context);
    }
    
    /**
     * @inheritDoc
     */
    public function findCollection(ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        throw new NoCollectionException($context->getResourceType());
    }
    
    /**
     * Finds the root line for the page with the given id
     *
     * @param   int                                                                  $id
     * @param   \LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext  $context
     *
     * @return \LaborDigital\T3fa\Resource\Entity\PageRootLineEntity|null*
     */
    protected function findRootLine(int $id, ResourceContext $context): ?PageRootLineEntity
    {
        $language = $context->getTypoContext()->language()->getId();
        
        $config = $context->getTypoContext()->t3fa()->getConfigValue('page', []);
        
        $additionalFields = is_array($config['additionalRootLineFields'] ?? null)
            ? $config['additionalRootLineFields'] : [];
        
        /** @var RootLineDataProviderInterface[] $dataProviders */
        $dataProviders = [];
        $dataProviderClasses = is_array($config['rootLineDataProviders'] ?? null)
            ? $config['rootLineDataProviders'] : [];
        foreach ($dataProviderClasses as $dataProviderClass) {
            $dataProviders[] = $this->getService($dataProviderClass);
        }
        
        $rootLine = array_reverse(
            $this->pageService->getRootLine($id, ['additionalFields' => $additionalFields])
        );
        
        $e = $this->eventDispatcher->dispatch(new PageRootLineFilterEvent(
            $id, $language, $rootLine
        ));
        
        $rootLine = array_values($e->getRootLine());
        
        $entries = [];
        foreach ($rootLine as $k => $item) {
            $entry = [
                'id' => $item['uid'],
                'parentId' => $item['pid'],
                'level' => $k,
                'title' => $item['title'],
                'navTitle' => $item['nav_title'],
                'link' => $this->linkService->getLink()->withPid($item['uid'])->build(['relative']),
            ];
            
            foreach ($additionalFields as $field) {
                $propertyName = Inflector::toCamelBack($field);
                $entry[$propertyName] = $item[$field] ?? null;
            }
            
            foreach ($dataProviders as $dataProvider) {
                $dataProvider->addData($id, $entry, $rootLine);
            }
            
            $entries[] = $entry;
        }
        
        return $this->makeInstance(
            PageRootLineEntity::class,
            [$id, $language, $entries]
        );
    }
}