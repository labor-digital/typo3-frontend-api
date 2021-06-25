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
 * Last modified: 2021.06.24 at 18:24
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Page\Generator;


use DOMDocument;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Tsfe\TsfeService;
use LaborDigital\T3fa\Api\Resource\Factory\Page\PageData;
use LaborDigital\T3fa\Event\Resource\Page\PageMetaTagsFilterEvent;
use Neunerlei\Arrays\Arrays;
use Neunerlei\PathUtil\Path;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Seo\MetaTag\MetaTagGenerator;

class MetaGenerator
{
    use ContainerAwareTrait;
    
    /**
     * A list of ignored meta properties we will never add to the frontend
     *
     * @var string[]
     */
    public static $ignoredProperties
        = [
            'og:image:url',
            'og:image:width',
            'og:image:height',
        ];
    
    /**
     * @var \LaborDigital\T3ba\Tool\Tsfe\TsfeService
     */
    protected $tsfeService;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry
     */
    protected $metaTagRegistry;
    
    public function __construct(
        TsfeService $tsfeService,
        EventDispatcherInterface $eventDispatcher,
        MetaTagManagerRegistry $metaTagRegistry
    )
    {
        $this->tsfeService = $tsfeService;
        $this->eventDispatcher = $eventDispatcher;
        $this->metaTagRegistry = $metaTagRegistry;
    }
    
    /**
     * Generates all page meta related attributes for the current page
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     */
    public function generate(PageData $data): void
    {
        if ($data->isRedirect) {
            return;
        }
        
        $e = $this->eventDispatcher->dispatch(new PageMetaTagsFilterEvent(
            $this->findMetaTags($data),
            $this->findHrefLangUrls($data),
            $data
        ));
        
        $data->attributes['meta'] = array_merge(
            $data->attributes['meta'],
            [
                'hrefLang' => $e->getHrefLangUrls(),
                'metaTags' => $e->getTags(),
            ]
        );
    }
    
    /**
     * Generates all required hreflang tags for the current page
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     */
    protected function findHrefLangUrls(PageData $data): array
    {
        $tsfe = $this->tsfeService->getTsfe();
        $pageBackup = $tsfe->page;
        $tsfe->page = $data->pageInfoArray;
        
        $languages = $this->makeInstance(LanguageMenuProcessor::class)
                          ->process($this->tsfeService->getContentObjectRenderer(), [], [], []);
        
        $urls = [];
        foreach ($languages['languagemenu'] as $language) {
            if ($language['available'] === 1 && ! empty($language['link'])) {
                $urls[] = [
                    'rel' => 'alternative',
                    'hreflang' => $language['hreflang'],
                    'href' => (string)Path::makeUri($this->getAbsoluteUrl($language['link'], $data))->withQuery(null),
                ];
            }
        }
        
        $tsfe->page = $pageBackup;
        
        return $urls;
    }
    
    /**
     * Generates the meta tag definitions for the current page
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return array
     */
    protected function findMetaTags(PageData $data): array
    {
        $info = $data->pageInfoArray;
        
        $this->processFallbackMetaTags($info);
        $this->processSeoExtensionTags($info, $data);
        $this->processTypoScriptMetaTags();
        
        $tagsString = '';
        foreach ($this->metaTagRegistry->getAllManagers() as $manager) {
            $tagsString .= $manager->renderAllProperties();
        }
        
        return $this->parseHtmlIntoArray($tagsString);
    }
    
    /**
     * Forces the given url to be absolute, relative to the current frontend language
     *
     * @param   string                                                 $url
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return string
     */
    protected function getAbsoluteUrl(string $url, PageData $data): string
    {
        $baseUrl = $data->attributes['links']['base'] ?? null;
        $uri = new Uri($url);
        if (empty($uri->getHost()) && ! empty($baseUrl)) {
            $absUrl = (new Uri($baseUrl))->withPath($uri->getPath());
            
            if ($uri->getQuery()) {
                return (string)$absUrl->withQuery($uri->getQuery());
            }
            
            return (string)$absUrl;
        }
        
        return $url;
    }
    
    /**
     * Generates the list of meta tags for the "seo" core extension
     *
     * @param   array                                                  $info
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     *
     * @return void
     */
    protected function processSeoExtensionTags(array $info, PageData $data): void
    {
        if (! class_exists(MetaTagGenerator::class)) {
            return;
        }
        
        $generator = $this->makeInstance(MetaTagGenerator::class);
        
        $slidedProps = [];
        $slidedProps = $this->generateSlidedProp($generator, $data, $slidedProps, 'og:image', 'og_image');
        $slidedProps = $this->generateSlidedProp($generator, $data, $slidedProps, 'twitter:image', 'twitter_image');
        
        $generator->generate(['page' => $info]);
        
        // Inject slided props
        if (! empty($slidedProps)) {
            foreach ($slidedProps as $prop => $definition) {
                $manager = $this->metaTagRegistry->getManagerForProperty($prop);
                $manager->addProperty($prop, $definition['content'], $definition['subProperties'], true);
            }
        }
    }
    
    /**
     * Tries to find additional meta tags based on backend fields
     *
     * @param   array  $info
     *
     * @return void
     */
    protected function processFallbackMetaTags(array $info): void
    {
        if (! empty($info['description'])) {
            $manager = $this->metaTagRegistry->getManagerForProperty('description');
            $manager->addProperty('description', trim($info['description']));
        }
        
        if (! empty($info['keywords'])) {
            $manager = $this->metaTagRegistry->getManagerForProperty('keywords');
            $manager->addProperty('description', implode(',', Arrays::makeFromStringList($info['keywords'])));
        }
    }
    
    /**
     * The main part of this method is a carbon copy of the RequestHandler::generateMetaTagHtml method.
     * It should extract all typo script meta tags and inject them into our meta tag list
     *
     * @return void
     * @see \TYPO3\CMS\Frontend\Http\RequestHandler::generateMetaTagHtml
     */
    protected function processTypoScriptMetaTags(): void
    {
        $setup = $this->tsfeService->getTsfe()->pSetup;
        if (! is_array($setup) || ! is_array($setup['meta.'] ?? null)) {
            return;
        }
        
        $cObj = $this->tsfeService->getTsfe()->cObj;
        $conf = $this->makeInstance(TypoScriptService::class)
                     ->convertTypoScriptArrayToPlainArray($setup['meta.']);
        
        foreach ($conf as $key => $properties) {
            $replace = false;
            if (is_array($properties)) {
                $nodeValue = $properties['_typoScriptNodeValue'] ?? '';
                $value = trim((string)$cObj->stdWrap($nodeValue, $setup['meta.'][$key . '.']));
                if ($value === '' && ! empty($properties['value'])) {
                    $value = $properties['value'];
                    $replace = false;
                }
            } else {
                $value = $properties;
            }
            
            $attribute = 'name';
            if ((is_array($properties) && ! empty($properties['httpEquivalent'])) || strtolower($key) === 'refresh') {
                $attribute = 'http-equiv';
            }
            if (is_array($properties) && ! empty($properties['attribute'])) {
                $attribute = $properties['attribute'];
            }
            if (is_array($properties) && ! empty($properties['replace'])) {
                $replace = true;
            }
            
            if (! is_array($value)) {
                $value = (array)$value;
            }
            
            foreach ($value as $subValue) {
                if (trim($subValue ?? '') !== '') {
                    /** @see \TYPO3\CMS\Core\Page\PageRenderer::setMetaTag() */
                    $attribute = strtolower($attribute);
                    $key = strtolower($key);
                    if (! in_array($attribute, ['property', 'name', 'http-equiv'], true)) {
                        throw new \InvalidArgumentException(
                            'When setting a meta tag the only types allowed are property, name or http-equiv. "' . $attribute . '" given.',
                            1496402460
                        );
                    }
                    
                    $manager = $this->metaTagRegistry->getManagerForProperty($key);
                    $manager->addProperty($key, $subValue, [], $replace);
                }
            }
        }
    }
    
    /**
     * Internal helper which is used to resolve slided meta properties -> Used when the page
     * has a slided image field like for og:image or twitter:image
     *
     * @param   \TYPO3\CMS\Seo\MetaTag\MetaTagGenerator                $generator
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\Page\PageData  $data
     * @param   array                                                  $list
     * @param   string                                                 $property
     * @param   string                                                 $fieldName
     *
     * @return array
     */
    protected function generateSlidedProp(MetaTagGenerator $generator, PageData $data, array $list, string $property, string $fieldName): array
    {
        if (! isset($data->slideFieldPidMap[$fieldName])) {
            return $list;
        }
        
        $ogImagePageInfo = $data->slideParentPageInfoMap[$data->slideFieldPidMap[$fieldName]];
        $generator->generate(['page' => $ogImagePageInfo]);
        $props = $this->metaTagRegistry->getManagerForProperty($property)->getProperty($property);
        $list[$property] = reset($props);
        
        return $list;
    }
    
    /**
     * Splits the given html string up into an array containing the attribute values
     *
     * @param   string  $tagsString
     *
     * @return array
     */
    protected function parseHtmlIntoArray(string $tagsString): array
    {
        $html = new DOMDocument();
        $html->loadHTML('<?xml encoding="utf-8" ?>' . $tagsString);
        foreach ($html->getElementsByTagName('meta') as $node) {
            /** @var \DOMElement $node */
            $attributes = [];
            foreach ($node->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }
            
            // Ignore og:image metadata to make them easier to overwrite in the frontend
            if (in_array($attributes['property'], ['og:image:url', 'og:image:width', 'og:image:height'])) {
                continue;
            }
            
            $tags[] = $attributes;
        }
        
        return $tags;
    }
}