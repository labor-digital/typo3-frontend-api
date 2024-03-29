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
 * Last modified: 2021.06.24 at 19:02
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Page\Generator;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;
use LaborDigital\T3fa\Event\Resource\Page\PageRootLineFilterEvent;
use LaborDigital\T3fa\ExtConfigHandler\Api\Page\RootLineDataProviderInterface;
use Neunerlei\Inflection\Inflector;
use Psr\EventDispatcher\EventDispatcherInterface;

class RootLineGenerator
{
    use ContainerAwareTrait;
    use TypoContextAwareTrait;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LinkService $linkService
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->linkService = $linkService;
    }
    
    /**
     * Finds the root line for the page with the given id
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return void
     */
    public function generate(PageData $data): void
    {
        if ($data->isRedirect) {
            return;
        }
        
        $data->attributes['meta']['rootLine'] = $this->findRootLine($data);
    }
    
    /**
     * Finds the root line for the current page and applies the registered data providers to it
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     */
    protected function findRootLine(PageData $data): array
    {
        $config = $this->getTypoContext()->config()->getSiteBasedConfigValue('t3fa.page', []);
        
        /** @var RootLineDataProviderInterface[] $dataProviders */
        $dataProviders = [];
        foreach ($config['rootLineDataProviders'] ?? [] as $dataProviderClass) {
            $dataProviders[] = $this->getServiceOrInstance($dataProviderClass);
        }
        
        $e = $this->eventDispatcher->dispatch(new PageRootLineFilterEvent(
            $data->uid, $data->language, array_reverse($data->rootLine ?? [])
        ));
        
        $rootLine = array_values($e->getRootLine());
        
        $entries = [];
        foreach ($rootLine as $k => $item) {
            $entry = [
                'id' => $item['uid'],
                'parentId' => $item['pid'],
                'lang' => $data->language->getTwoLetterIsoCode(),
                'level' => $k,
                'title' => $item['title'],
                'navTitle' => $item['nav_title'],
                'doktype' => $item['doktype'],
                'link' => $this->linkService->getLink()->withPid($item['uid'])->build(['relative']),
            ];
            
            foreach ($config['additionalRootLineFields'] ?? [] as $field) {
                $propertyName = Inflector::toCamelBack($field);
                $entry[$propertyName] = $item[$field] ?? null;
            }
            
            foreach ($dataProviders as $dataProvider) {
                $dataProvider->addData($data->uid, $entry, $rootLine);
            }
            
            $entries[] = $entry;
        }
        
        return $entries;
    }
}