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
 * Last modified: 2021.06.24 at 18:12
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Page\Generator;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\EventBus\TypoEventBus;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\Tsfe\TsfeService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;
use LaborDigital\T3fa\ExtConfigHandler\Api\Page\Link\PageLinkCollector;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Seo\Canonical\CanonicalGenerator;

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
    public function generate(PageData $data): void
    {
        if ($data->isRedirect) {
            return;
        }
        
        $data->attributes['links'] = $this->findLinkList($data);
    }
    
    /**
     * Generates the "links" list for the page resource
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     */
    protected function findLinkList(PageData $data): array
    {
        $collector = $this->makeInstance(PageLinkCollector::class, [$this->linkService]);
        
        $collector->registerStaticLink('base', (string)$this->findBaseUrl($data));
        $collector->registerStaticLink('canonical', $this->generateCanonicalUrl());
        
        foreach ($this->context->config()->getSiteBasedConfigValue('t3fa.page.linkProviders', []) as $linkProvider) {
            $linkProvider::provideLinks($collector, $this->linkService, $data->uid);
        }
        
        return $collector->getAll();
    }
    
    /**
     * Generates the base url for this page
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return \Psr\Http\Message\UriInterface
     */
    protected function findBaseUrl(PageData $data): UriInterface
    {
        return $data->language->getBase();
    }
    
    /**
     * Generates the canonical url of the current page
     *
     * @return string
     */
    protected function generateCanonicalUrl(): string
    {
        // Fallback if seo extension is not installed
        if (! class_exists(CanonicalGenerator::class)) {
            return $this->linkService->getLink()->build();
        }
        
        // Use seo extension to generate canonical url
        $generator = $this->makeInstance(CanonicalGenerator::class, [
            $this->getService(TsfeService::class)->getTsfe(),
            TypoEventBus::getInstance(),
        ]);
        
        preg_match('~href="(.*?)"~', $generator->generate(), $m);
        
        return $m[1] ?? '';
    }
}