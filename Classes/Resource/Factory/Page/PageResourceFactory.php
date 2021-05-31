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
 * Last modified: 2021.05.31 at 12:24
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Resource\Factory\Page;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Page\PageService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Resource\Entity\PageEntity;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class PageResourceFactory
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Page\PageService
     */
    protected $pageService;
    
    /**
     * @var \LaborDigital\T3fa\Resource\Factory\Page\LinkGenerator
     */
    protected $linkGenerator;
    
    public function __construct(TypoContext $typoContext, PageService $pageService, LinkGenerator $linkGenerator)
    {
        $this->typoContext = $typoContext;
        $this->pageService = $pageService;
        $this->linkGenerator = $linkGenerator;
    }
    
    public function make(int $pid, SiteLanguage $language, SiteInterface $site): PageEntity
    {
        return $this->makeInstance(
            PageEntity::class,
            [
                $pid,
                $language,
                $site,
                $this->getLinks($pid),
            ]
        );
    }
    
    /**
     * Builds the links array for the instantiated page
     *
     * @param   int  $pid
     *
     * @return array
     */
    protected function getLinks(int $pid): array
    {
        return $this->linkGenerator->generate($pid);
    }
}