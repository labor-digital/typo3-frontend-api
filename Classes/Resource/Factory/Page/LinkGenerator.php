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
 * Last modified: 2021.05.31 at 12:03
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Resource\Factory\Page;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\Link\PageLinkCollector;

class LinkGenerator
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $context;
    
    public function __construct(LinkService $linkService, TypoContext $context)
    {
        $this->linkService = $linkService;
        $this->context = $context;
    }
    
    /**
     * Executes all registered link providers and returns the list of the generated links as array
     *
     * @param   int  $pid  The pid of the current page
     *
     * @return array
     */
    public function generate(int $pid): array
    {
        $collector = $this->makeInstance(PageLinkCollector::class, [$this->linkService]);
        
        foreach ($this->context->t3fa()->getConfigValue('page.linkProviders', []) as $linkProvider) {
            $linkProvider::provideLinks($collector, $this->linkService, $pid);
        }
        
        return $collector->getAll();
    }
}