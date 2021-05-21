<?php
/*
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.10.05 at 19:48
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page;


use DOMDocument;
use LaborDigital\Typo3BetterApi\Container\ContainerAwareTrait;
use LaborDigital\Typo3BetterApi\Event\TypoEventBus;
use LaborDigital\Typo3FrontendApi\Event\PageMetaTagsFilterEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData;
use Neunerlei\Arrays\Arrays;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Seo\MetaTag\MetaTagGenerator;

class PageDataMetaTagGenerator implements SingletonInterface
{
    use ContainerAwareTrait;

    /**
     * @var \LaborDigital\Typo3BetterApi\Event\TypoEventBus
     */
    protected $eventBus;

    /**
     * @var \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry
     */
    protected $metaTagManagerRegistry;

    /**
     * PageDataMetaTagGenerator constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\TypoEventBus  $eventBus
     * @param   \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry   $metaTagManagerRegistry
     */
    public function __construct(TypoEventBus $eventBus, MetaTagManagerRegistry $metaTagManagerRegistry)
    {
        $this->eventBus               = $eventBus;
        $this->metaTagManagerRegistry = $metaTagManagerRegistry;
    }

    /**
     * Internal helper to generate the meta tag definitions for the given page object
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData  $value
     *
     * @return array
     */
    public function getMetaTags(PageData $value): array
    {
        // Reset all managers
        foreach ($this->metaTagManagerRegistry->getAllManagers() as $manager) {
            $manager->removeAllProperties();
        }

        // Prepare storage
        $pageInfo   = $value->getPageInfo();
        $tags       = [];
        $knownNodes = [];

        // Make sure the seo extension is installed
        if (class_exists(MetaTagGenerator::class)) {
            $this->generatePageProperties($pageInfo, $value);

            // Convert the properties into an object
            $tagsString = '';
            foreach ($this->metaTagManagerRegistry->getAllManagers() as $manager) {
                $tagsString .= $manager->renderAllProperties();
            }

            $tags = $this->parseHtmlIntoArray($tagsString, $knownNodes);
        }

        // Add additional meta tags
        if (! empty($pageInfo['description']) && ! in_array('description', $knownNodes, true)) {
            $tags[] = [
                'name'    => 'description',
                'content' => trim($pageInfo['description']),
            ];
        }
        if (! empty($pageInfo['keywords']) && ! in_array('keywords', $knownNodes, true)) {
            $tags[] = [
                'name'    => 'keywords',
                'content' => implode(',', Arrays::makeFromStringList($pageInfo['keywords'])),
            ];
        }

        // Allow filtering
        $this->eventBus->dispatch(($e = new PageMetaTagsFilterEvent($tags, $value)));

        return $e->getTags();
    }


    /**
     * Fills the meta tag manager with the default properties for a page
     *
     * @param   array     $pageInfo
     * @param   PageData  $value
     */
    protected function generatePageProperties(array $pageInfo, PageData $value): void
    {
        // Prepare the generator
        $generator = $this->getInstanceOf(MetaTagGenerator::class);

        // Check if the twitter / og images got slided
        $slidedProps = [];
        $slidedProps = $this->generateSlidedProp($generator, $value, $slidedProps, 'og:image', 'og_image');
        $slidedProps = $this->generateSlidedProp($generator, $value, $slidedProps, 'twitter:image', 'twitter_image');

        // Prepare the registry for the default properties
        foreach ($this->metaTagManagerRegistry->getAllManagers() as $manager) {
            $manager->removeAllProperties();
        }
        $generator->generate(['page' => $pageInfo]);

        // Inject slided props
        if (! empty($slidedProps)) {
            foreach ($slidedProps as $prop => $definition) {
                $manager = $this->metaTagManagerRegistry->getManagerForProperty($prop);
                $manager->removeProperty($prop);
                $manager->addProperty($prop, $definition['content'], $definition['subProperties']);
            }
        }
    }

    /**
     * Internal helper which is used to resolve slided meta properties -> Used when the page
     * has a slided image field like for og:image or twitter:image
     *
     * @param   \TYPO3\CMS\Seo\MetaTag\MetaTagGenerator                                  $generator
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData  $value
     * @param   array                                                                    $list
     * @param   string                                                                   $property
     * @param   string                                                                   $fieldName
     *
     * @return array
     */
    protected function generateSlidedProp(MetaTagGenerator $generator, PageData $value, array $list, string $property, string $fieldName): array
    {
        $slided = $value->getSlideFieldPidMap();
        if (! isset($slided[$fieldName])) {
            return $list;
        }
        $ogImagePageInfo = $value->getSlideParentPageInfoMap()[$slided[$fieldName]];
        $generator->generate(['page' => $ogImagePageInfo]);
        $props           = $this->metaTagManagerRegistry->getManagerForProperty($property)->getProperty($property);
        $list[$property] = reset($props);

        return $list;
    }

    /**
     * Splits the given html string up into an array containing the attribute values
     *
     * @param   string  $tagsString
     * @param   array   $knownNodes
     *
     * @return array
     */
    protected function parseHtmlIntoArray(string $tagsString, array &$knownNodes): array
    {
        $knownNodes = [];
        $html       = new DOMDocument();
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
            if (isset($attributes['name'])) {
                $knownNodes[] = $attributes['name'];
            }

        }

        return $tags;
    }
}
