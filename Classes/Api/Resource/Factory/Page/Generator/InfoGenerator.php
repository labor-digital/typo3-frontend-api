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
 * Last modified: 2021.06.13 at 22:56
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Page\Generator;


use LaborDigital\T3ba\Tool\Database\DbService;
use LaborDigital\T3ba\Tool\ExtBase\Hydrator\Hydrator;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\Page\PageService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;
use LaborDigital\T3fa\Configuration\Table\Override\BackendLayoutTable;
use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory;
use LaborDigital\T3fa\Core\Routing\Util\RedirectUtil;
use LaborDigital\T3fa\Domain\DataModel\Page\DefaultPageDataModel;
use Neunerlei\Inflection\Inflector;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class InfoGenerator
{
    /**
     * @var \LaborDigital\T3ba\Tool\Page\PageService
     */
    protected $pageService;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3ba\Tool\ExtBase\Hydrator\Hydrator
     */
    protected $hydrator;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory
     */
    protected $resourceFactory;
    
    public function __construct(
        PageService $pageService,
        TypoContext $context,
        EventDispatcherInterface $eventDispatcher,
        Hydrator $hydrator,
        LinkService $linkService,
        ResourceFactory $resourceFactory
    )
    {
        $this->pageService = $pageService;
        $this->typoContext = $context;
        $this->eventDispatcher = $eventDispatcher;
        $this->hydrator = $hydrator;
        $this->linkService = $linkService;
        $this->resourceFactory = $resourceFactory;
    }
    
    /**
     * Generates all relevant page information
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     */
    public function generate(PageData $data): void
    {
        $data->pageInfoArray = $this->findInfoArray($data);
        $redirect = $this->findRedirectConfig($data);
        if ($redirect) {
            $data->isRedirect = true;
            $data->attributes = $redirect;
            
            return;
        }
        
        $data->attributes = $this->findAttributes($data);
        
        $data->attributes['meta']['site'] = $data->site->getIdentifier();
        $data->attributes['meta']['layout'] = $this->findLayout($data);
        $data->attributes['meta']['language'] = $data->language->getTwoLetterIsoCode();
        $data->attributes['meta']['languages'] = $this->findSiteLanguages($data);
    }
    
    /**
     * Generates the page info array with all slide fields applied
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     * @throws \LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException
     */
    protected function findInfoArray(PageData $data): array
    {
        $info = $this->pageService->getPageInfo($data->pid);
        
        if (empty($info)) {
            throw new ResourceNotFoundException('There is no page with the required pid: ' . $data->pid);
        }
        
        $info = $this->applySlideFields($info, $data);
        
        // @todo implement this
//        $info = $this->eventDispatcher->dispatch(new PageDataPageInfoFilterEvent($data->pid, $info, $data))->getInfo();
        
        return $info;
    }
    
    /**
     * Finds the redirect information for this page
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $pageData
     *
     * @return string|null
     */
    protected function findRedirectConfig(PageData $pageData): ?array
    {
        $info = $pageData->pageInfoArray;
        switch ((int)$info['doktype']) {
            case PageRepository::DOKTYPE_SHORTCUT:
                $shortcutInfo = $this->pageService->getPageRepository()->getPageShortcut(
                    $info['shortcut'],
                    $info['shortcut_mode'],
                    $info['uid']
                );
                
                if (is_array($shortcutInfo) && ! empty($shortcutInfo['uid'])) {
                    $url = $this->linkService->getLink()->withPid($shortcutInfo['uid'])->build(['relative']);
                    
                    return RedirectUtil::makeRedirectAttribute($url);
                }
                
                break;
            case PageRepository::DOKTYPE_LINK:
                if (! empty($info['url'])) {
                    return RedirectUtil::makeRedirectAttribute((string)$info['url'], $info['target']);
                }
        }
        
        return null;
    }
    
    /**
     * Helper to apply the slide fields on the raw database data to inherit data from the parent pages
     *
     * @param   array  $info
     * @param   array  $slideFields
     *
     * @return array
     */
    protected function applySlideFields(array $info, PageData $data): array
    {
        $slideFields = $this->typoContext->t3fa()->getConfigValue('page.dataSlideFields', []);
        if (empty($slideFields)) {
            return $info;
        }
        
        foreach (array_intersect_key($info, array_fill_keys($slideFields, null)) as $k => $v) {
            if (! empty($info[$k])) {
                continue;
            }
            
            foreach ($this->findRawRootLine($data) as $parentPageInfo) {
                if (empty($parentPageInfo[$k])) {
                    continue;
                }
                
                $info[$k] = $parentPageInfo[$k];
                $data->slideFieldPidMap[$k] = $parentPageInfo['uid'];
                $data->slideParentPageInfoMap[$parentPageInfo['uid']] = $parentPageInfo;
                continue 2;
            }
        }
        
        return $info;
    }
    
    /**
     * Helper to retrieve or generate the root line based including slide and additional fields
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     */
    protected function findRawRootLine(PageData $data): array
    {
        if (isset($data->rootLine)) {
            return $data->rootLine;
        }
        
        $t3fa = $this->typoContext->t3fa();
        $additionalFields = array_unique(
            array_merge(
                $t3fa->getConfigValue('page.dataSlideFields', []),
                $t3fa->getConfigValue('page.additionalRootLineFields', [])
            )
        );
        
        return $data->rootLine
            = $this->pageService->getRootLine($data->pid, ['additionalFields' => $additionalFields]);
    }
    
    /**
     * Finds the attributes array based on the configured data model class
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     */
    protected function findAttributes(PageData $data): array
    {
        $model = $this->hydrateDataModel($data);
        $item = $this->resourceFactory->makeResourceItem($model);
        
        $attributes = $item->asArray(['include' => true])['data'] ?? [];
        // It feels more intuitive to remove the nested "data" in inlined includes here
        foreach ($attributes as $k => $v) {
            if (is_array($v) && is_array($v['data'] ?? null)) {
                $attributes[$k] = $v['data'];
            }
        }
        
        $attributes['meta'] = [];
        
        return $attributes;
    }
    
    /**
     * Hydrates the configured page data model class with the already fetched page info array
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
     */
    protected function hydrateDataModel(PageData $data): AbstractEntity
    {
        $modelClass = $this->typoContext->t3fa()->getConfigValue('page.dataModelClass', DefaultPageDataModel::class);
        $model = $this->hydrator->hydrateObject(
            $modelClass,
            $data->pageInfoArray
        );
        
        $this->applySlideProperties($data, $modelClass, $model);
        
        // @todo add an event here
        
        return $model;
    }
    
    /**
     * Helper to apply slided properties to the generated model class
     * This is required because extbase does not have the concept of sliding, so we have
     * to manually resolve the slided data based on the parent records
     *
     * @param   PageData        $data
     * @param   string          $modelClass
     * @param   AbstractEntity  $model
     */
    protected function applySlideProperties(PageData $data, string $modelClass, AbstractEntity $model): void
    {
        $props = $model->_getProperties();
        $slideableProps = array_combine(array_map(
            [Inflector::class, 'toProperty'],
            array_keys($data->slideFieldPidMap)
        ), $data->slideFieldPidMap);
        
        // Check if we have props to slide -> Ignore if not...
        $slidedProps = array_intersect_key($slideableProps, $props);
        
        if (empty($slidedProps)) {
            return;
        }
        
        $parentModels = [];
        foreach ($slidedProps as $slidedProp => $parentPid) {
            if (! isset($parentModels[$parentPid])) {
                $parentModels[$parentPid]
                    = $this->hydrator->hydrateObject($modelClass, $data->slideParentPageInfoMap[$parentPid]);
            }
            
            $parent = $parentModels[$parentPid];
            $model->_setProperty($slidedProp, $parent->_getProperty($slidedProp));
            $model->_memorizeCleanState($slidedProp);
        }
        
        unset($parentModels);
    }
    
    /**
     * Tries to retrieve the layout configuration for this page.
     * All parent pages backend_layout_next_level fields will be taken into account
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return string
     */
    protected function findLayout(PageData $data): string
    {
        $layoutField = $this->typoContext->t3fa()->getConfigValue('page.layoutField', 'backend_layout');
        $info = $data->pageInfoArray;
        
        if (! empty($info[$layoutField])) {
            return $this->findLayoutIdentifier((int)$info[$layoutField]);
        }
        
        foreach ($this->findRawRootLine($data) as $_info) {
            if (! empty($_info[$layoutField]) && is_numeric($_info[$layoutField])) {
                return $this->findLayoutIdentifier((int)$_info[$layoutField]);
            }
            
            if (! empty($_info[$layoutField . '_next_level']) && is_numeric($_info[$layoutField . '_next_level'])) {
                return $this->findLayoutIdentifier((int)$_info[$layoutField . '_next_level']);
            }
        }
        
        return 'default';
    }
    
    /**
     * Tries to convert the layout uid into a more speaking identifier.
     * If no identifier was configured, the numeric layout id will be returned as a string
     *
     * @param   int  $layoutId  The layout uid to find an identifier for
     *
     * @return string
     */
    protected function findLayoutIdentifier(int $layoutId): string
    {
        $query = $this->typoContext->di()->getService(DbService::class)->getQuery(BackendLayoutTable::class);
        $row = $query->withWhere(['uid' => $layoutId])->getFirst(['t3fa_identifier']);
        $identifier = ($row ?? [])['t3fa_identifier'] ?? '';
        
        if (empty(trim($identifier ?? ''))) {
            return (string)$layoutId;
        }
        
        return $identifier;
    }
    
    /**
     * Retrieves the iso codes of all enabled site languages and returns them
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     */
    protected function findSiteLanguages(PageData $data): array
    {
        return array_map(static function (SiteLanguage $language): string {
            return $language->getTwoLetterIsoCode();
        }, $data->site->getLanguages());
    }
}