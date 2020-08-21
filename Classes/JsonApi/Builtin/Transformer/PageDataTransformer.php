<?php
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.09.20 at 18:44
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer;


use DOMDocument;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\Event\PageMetaTagsFilterEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;
use Neunerlei\Arrays\Arrays;
use Neunerlei\PathUtil\Path;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Seo\Canonical\CanonicalGenerator;
use TYPO3\CMS\Seo\MetaTag\MetaTagGenerator;

class PageDataTransformer extends AbstractResourceTransformer
{
    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $context;

    /**
     * @var \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry
     */
    protected $metaTagManagerRegistry;

    /**
     * PageDataTransformer constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext  $context
     * @param   \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry        $metaTagManagerRegistry
     */
    public function __construct(TypoContext $context, MetaTagManagerRegistry $metaTagManagerRegistry)
    {
        $this->context                = $context;
        $this->metaTagManagerRegistry = $metaTagManagerRegistry;
    }

    /**
     * @inheritDoc
     */
    protected function transformValue($value): array
    {
        /** @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData $value */
        $pageObject             = $value->getData();
        $result                 = $this->autoTransform($pageObject, ["allIncludes"]);
        $result["metaTags"]     = $this->getMetaTags($value);
        $result["canonicalUrl"] = $this->getCleanCanonicalUrl();

        return $result;
    }

    /**
     * Internal helper to generate the canonical url for the current page
     *
     * @return string
     */
    protected function getCleanCanonicalUrl(): string
    {
        $requestBackup            = $GLOBALS['TYPO3_REQUEST'];
        $GLOBALS['TYPO3_REQUEST'] = $this->context->Request()->getRootRequest()->withQueryParams([]);
        if (! class_exists(CanonicalGenerator::class)) {
            return $this->Links()->getLink()->build();
        }
        $canonicalTag = $this->getInstanceOf(CanonicalGenerator::class)->generate();
        preg_match("~href=\"(.*?)\"~", $canonicalTag, $m);
        $url                      = $m[1];
        $url                      = (string)Path::makeUri($url)->withQuery(null);
        $GLOBALS['TYPO3_REQUEST'] = $requestBackup;

        return $url;
    }

    /**
     * Internal helper to generate the meta tag definitions for the given page object
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData  $value
     *
     * @return array
     */
    protected function getMetaTags(PageData $value): array
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
            // Prepare the generator
            $generator = $this->getInstanceOf(MetaTagGenerator::class);

            // Check if the twitter / og images got slided
            $slidedProps = [];
            $slidedProps = $this->generateSlidedProp($generator, $value, $slidedProps, "og:image", "og_image");
            $slidedProps = $this->generateSlidedProp($generator, $value, $slidedProps, "twitter:image", "twitter_image");

            // Prepare the registry for the default properties
            foreach ($this->metaTagManagerRegistry->getAllManagers() as $manager) {
                $manager->removeAllProperties();
            }
            $this->getInstanceOf(MetaTagGenerator::class)->generate(["page" => $pageInfo]);
            $tagsString = "";

            // Inject slided props
            if (! empty($slidedProps)) {
                foreach ($slidedProps as $prop => $definition) {
                    $manager = $this->metaTagManagerRegistry->getManagerForProperty($prop);
                    $manager->removeProperty($prop);
                    $manager->addProperty($prop, $definition["content"], $definition["subProperties"]);
                }
            }

            // Convert the properties into an object
            foreach ($this->metaTagManagerRegistry->getAllManagers() as $manager) {
                $tagsString .= $manager->renderAllProperties();
            }
            $html = new DOMDocument("1.0", "utf-8");
            $html->loadHTML($tagsString);
            foreach ($html->getElementsByTagName("meta") as $node) {
                /** @var \DOMElement $node */
                $attributes = [];
                foreach ($node->attributes as $attr) {
                    $attributes[$attr->nodeName] = $attr->nodeValue;
                }
                // Ignore og:image metadata to make them easier to overwrite in the frontend
                if (in_array($attributes["property"], ["og:image:url", "og:image:width", "og:image:height"])) {
                    continue;
                }
                $tags[] = $attributes;
                if (isset($attributes["name"])) {
                    $knownNodes[] = $attributes["name"];
                }
            }
        }

        // Add additional meta tags
        if (! empty($pageInfo["description"]) && ! in_array("description", $knownNodes)) {
            $tags[] = [
                "name"    => "description",
                "content" => trim($pageInfo["description"]),
            ];
        }
        if (! empty($pageInfo["keywords"]) && ! in_array("keywords", $knownNodes)) {
            $tags[] = [
                "name"    => "keywords",
                "content" => implode(",", Arrays::makeFromStringList($pageInfo["keywords"])),
            ];
        }

        // Allow filtering
        $this->EventBus()->dispatch(($e = new PageMetaTagsFilterEvent($tags, $value)));

        return $e->getTags();
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
        $generator->generate(["page" => $ogImagePageInfo]);
        $list[$property]
            = reset($this->metaTagManagerRegistry->getManagerForProperty($property)->getProperty($property));

        return $list;
    }
}
