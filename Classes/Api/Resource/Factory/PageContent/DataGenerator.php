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
 * Last modified: 2021.06.13 at 21:42
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\PageContent;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Page\PageService;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3fa\Api\Resource\Factory\ContentElement\ContentElementResourceFactory;
use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class DataGenerator implements PublicServiceInterface
{
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Page\PageService
     */
    protected $pageService;
    
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\ContentElement\ContentElementResourceFactory
     */
    protected $elementFactory;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory
     */
    protected $resourceFactory;
    
    
    public function __construct(
        EnvironmentSimulator $simulator,
        EventDispatcherInterface $eventDispatcher,
        PageService $pageService,
        ContentElementResourceFactory $elementFactory,
        ResourceFactory $resourceFactory
    )
    {
        $this->simulator = $simulator;
        $this->eventDispatcher = $eventDispatcher;
        $this->pageService = $pageService;
        $this->elementFactory = $elementFactory;
        $this->resourceFactory = $resourceFactory;
    }
    
    /**
     * Generates the constructor arguments for a page content entity
     *
     * @param   int                                        $pid
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage   $language
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteInterface  $site
     *
     * @return array
     */
    public function generate(int $pid, SiteLanguage $language, SiteInterface $site): array
    {
        return $this->simulator->runWithEnvironment(
            [
                'pid' => $pid,
                'site' => $site->getIdentifier(),
                'language' => $language,
            ],
            function () use ($pid, $language, $site) {
                return $this->run($pid, $language, $site);
            }
        );
    }
    
    /**
     * Executed in the scope of the given pid language and site, only if no cache entry was found
     * for this configuration. Will retrieve the page content and process all elements in the found columns.
     *
     * @param   int                                        $pid
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage   $language
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteInterface  $site
     *
     * @return array
     */
    protected function run(int $pid, SiteLanguage $language, SiteInterface $site): array
    {
        $cols = [];
        
        // There is a lot of duplicated actions here
        // PageService will apply all overlays and basically serve the data that would be needed
        // by the content elements. However we can't serve the data to the content elements,
        // because TYPO3s content elements are that generic. But to resolve potential children
        // of extensions like grid elements we need the data to be resolved.
        // If I ever come across a better solution, I will rewrite this.
        foreach ($this->pageService->getPageContents($pid) as $colPos => $elements) {
            $cols[$colPos] = $this->processColumn($elements);
        }
        
        return array_values([
            'id' => $pid,
            'attributes' => [
                'children' => $cols,
                'meta' => [
                    'language' => $language->getTwoLetterIsoCode(),
                    'site' => $site->getIdentifier(),
                ],
            ],
        ]);
    }
    
    /**
     * Creates and transforms the given list of content elements
     * Automatically detects recursively nested elements and processes them too.
     *
     * @param   array  $elements
     *
     * @return array
     */
    protected function processColumn(array $elements): array
    {
        $col = [];
        
        foreach ($elements as $element) {
            $el = $this->elementFactory->makeFromRow($element['record'], function () use ($element) {
                if (! empty($element['children'])) {
                    $childCols = [];
                    foreach ($element['children'] as $colPos => $childElements) {
                        $childCols[$colPos] = $this->processColumn($childElements);
                    }
                    
                    return $childCols;
                }
                
                return null;
            });
            
            $col[] = $this->resourceFactory->makeResourceItem($el)->asArray([
                'include' => true,
                'jsonApi',
            ]);
        }
        
        return $col;
    }
}