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
 * Last modified: 2021.06.24 at 18:29
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Page;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\InfoGenerator;
use LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\LinkGenerator;
use LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\MetaGenerator;
use LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\RootLineGenerator;
use LaborDigital\T3fa\Event\Resource\Page\PageAttributesFilterEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class DataGenerator implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\InfoGenerator
     */
    protected $infoGenerator;
    
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\LinkGenerator
     */
    protected $linkGenerator;
    
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\RootLineGenerator
     */
    protected $rootLineGenerator;
    
    /**
     * The currently generated page data or null if not actively generating
     *
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData
     */
    protected $data;
    
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Page\Generator\MetaGenerator
     */
    protected $metaGenerator;
    
    public function __construct(
        EnvironmentSimulator $simulator,
        EventDispatcherInterface $eventDispatcher,
        InfoGenerator $infoGenerator,
        LinkGenerator $linkGenerator,
        RootLineGenerator $rootLineGenerator,
        MetaGenerator $metaGenerator
    )
    {
        $this->simulator = $simulator;
        $this->eventDispatcher = $eventDispatcher;
        $this->infoGenerator = $infoGenerator;
        $this->linkGenerator = $linkGenerator;
        $this->rootLineGenerator = $rootLineGenerator;
        $this->metaGenerator = $metaGenerator;
    }
    
    /**
     * Generates the constructor arguments for a single Page resource entity
     *
     * @param   int                                        $pid
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage   $language
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteInterface  $site
     *
     * @return array
     */
    public function generate(int $pid, SiteLanguage $language, SiteInterface $site): array
    {
        $this->data = $this->makeInstance(PageData::class, [$pid, $language, $site]);
        
        try {
            $this->simulator->runWithEnvironment(
                [
                    'pid' => $pid,
                    'site' => $site->getIdentifier(),
                    'language' => $language,
                ],
                [$this, 'runGenerators']
            );
            
            return $this->data->getConstructorArgs();
            
        } finally {
            $this->data = null;
        }
    }
    
    /**
     * Executes the internal data generators in order to fill the current data object
     *
     * @internal You should always use generate()!
     */
    public function runGenerators(): void
    {
        if (! $this->data) {
            return;
        }
        
        $this->infoGenerator->generate($this->data);
        $this->linkGenerator->generate($this->data);
        $this->metaGenerator->generate($this->data);
        $this->rootLineGenerator->generate($this->data);
        
        $this->eventDispatcher->dispatch(new PageAttributesFilterEvent($this->data));
    }
}