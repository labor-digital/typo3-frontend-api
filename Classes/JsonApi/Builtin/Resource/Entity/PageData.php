<?php
declare(strict_types=1);
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
 * Last modified: 2019.09.20 at 18:42
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\ArrayBasedCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\Event\PageDataPageInfoFilterEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page\PageDataLinkGenerator;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page\PageDataMetaTagGenerator;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use LaborDigital\Typo3FrontendApi\Shared\ModelHydrationTrait;
use LaborDigital\Typo3FrontendApi\Shared\ShortTimeMemoryTrait;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class PageData implements SelfTransformingInterface
{
    use FrontendApiContextAwareTrait;
    use ModelHydrationTrait;
    use ShortTimeMemoryTrait;

    /**
     * The page id we hold the data for
     *
     * @var int
     */
    protected $id;

    /**
     * The two char iso language code for this element
     *
     * @var string
     */
    protected $languageCode;

    /**
     * The map of fields and their parent page uids to map references correctly
     *
     * @var array
     */
    protected $slideFieldPidMap = [];

    /**
     * The list of all parent page info objects for slided fields to create the slided model with
     *
     * @var array
     */
    protected $slideParentPageInfoMap = [];

    /**
     * PageData constructor.
     *
     * @param   int     $id  The pid of the page we should represent the data for
     * @param   string  $languageCode
     */
    public function __construct(int $id, string $languageCode)
    {
        $this->id           = $id;
        $this->languageCode = $languageCode;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        /** @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData $value */
        $context = $this->FrontendApiContext();

        return $context->CacheService()->remember(
            function () use ($value) {
                $result                 = $this->autoTransform($value->getData(), ['allIncludes']);
                $result['metaTags']     = $this->getMetaTags($value);
                $result['canonicalUrl'] = $this->getCleanCanonicalUrl();

                return $result;
            },
            [
                'tags'         => ['page_' . $value->getId(), 'pages_' . $value->getId()],
                'keyGenerator' => $context->getInstanceWithoutDi(ArrayBasedCacheKeyGenerator::class, [
                    [__CLASS__, $value->getId(), $value->getLanguageCode()],
                ]),
            ]
        );
    }

    /**
     * Returns the page id we hold the data for
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the two char iso language code for this element
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * Returns the page data representation as an object
     *
     * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
     * @throws \League\Route\Http\Exception\NotFoundException
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    public function getData(): AbstractEntity
    {
        return $this->remember(function () {
            $pageClass = $this->FrontendApiContext()->getCurrentSiteConfig()->pageDataClass;
            if (! class_exists($pageClass)) {
                throw new JsonApiException('The given page data class: ' . $pageClass . ' does not exist!');
            }
            $model = $this->hydrateModelObject($pageClass, 'pages', $this->getPageInfo());

            // Check if we have to update slided properties
            if (! empty($this->slideFieldPidMap)) {
                $this->applySlideProperties($pageClass, $model);
            }

            return $model;
        }, __FUNCTION__);
    }

    /**
     * Returns the raw page database row
     *
     * @return array
     * @throws \League\Route\Http\Exception\NotFoundException
     */
    public function getPageInfo(): array
    {
        return $this->remember(function () {
            $context  = $this->FrontendApiContext();
            $pageInfo = $context->Page()->getPageInfo($this->id);

            if (empty($pageInfo)) {
                throw new NotFoundException();
            }

            // Apply slide fields if required
            $slideFields = $context->getCurrentSiteConfig()->pageDataSlideFields;
            if (! empty($slideFields)) {
                $pageInfo = $this->applySlideFields($pageInfo, $slideFields);
            }

            // Allow filtering
            return $context->EventBus()->dispatch(
                new PageDataPageInfoFilterEvent($this->id, $pageInfo, $this->slideFieldPidMap)
            )->getRow();
        }, __FUNCTION__);
    }

    /**
     * Returns the map of fields and their parent page uids to map references correctly
     *
     * @return array
     */
    public function getSlideFieldPidMap(): array
    {
        return $this->remember(function () {
            // This has to be called in order for slideFieldPidMap to be filled
            $this->getPageInfo();

            return $this->slideFieldPidMap;
        }, __FUNCTION__);
    }

    /**
     * Returns the list of all parent page info objects for slided fields to create the slided model with
     *
     * @return array
     */
    public function getSlideParentPageInfoMap(): array
    {
        return $this->remember(function () {
            // This has to be called in order for slideParentPageInfoMap to be filled
            $this->getPageInfo();

            return $this->slideParentPageInfoMap;
        }, __FUNCTION__);
    }

    /**
     * Returns the list of generated meta tags for this page
     *
     * @return array
     */
    public function getMetaTags(): array
    {
        return $this->remember(function () {
            return $this->FrontendApiContext()->getSingletonOf(PageDataMetaTagGenerator::class)->getMetaTags($this);
        }, __FUNCTION__);
    }

    /**
     * Returns the canonical url for this data object
     *
     * @return string
     */
    public function getCanonicalUrl(): string
    {
        return $this->remember(function () {
            return $this->FrontendApiContext()->getSingletonOf(PageDataLinkGenerator::class)->makeCanonicalUrl($this);
        }, __FUNCTION__);
    }

    /**
     * Returns the list of href lang tags for this data object
     *
     * @return array
     */
    public function getHrefLangUrls(): array
    {
        return $this->remember(function () {
            return $this->FrontendApiContext()->getSingletonOf(PageDataLinkGenerator::class)->makeHrefLangUrls($this);
        }, __FUNCTION__);
    }

    /**
     * Helper to apply the slide fields on the raw database data to inherit data from the parent pages
     *
     * @param   array  $pageInfo
     * @param   array  $slideFields
     *
     * @return array
     */
    protected function applySlideFields(array $pageInfo, array $slideFields): array
    {
        // The list of all fields that can be slided (filled by the parent page)
        $fields = array_intersect_key($pageInfo, array_fill_keys($slideFields, null));

        // A helper to check if a field's value is empty
        $isEmpty = static function (string $field, array $pageInfo): bool {
            if (! array_key_exists($field, $pageInfo)) {
                return false;
            }
            if ($pageInfo[$field] === null || $pageInfo[$field] === '' || $pageInfo[$field] === '0') {
                return true;
            }

            return false;
        };

        // Generate the root line and prepare a cache to store resolved page information
        $pageService   = $this->FrontendApiContext()->Page();
        $rootLine      = $pageService->getRootLine($this->id);
        $pageInfoCache = [];

        // Run trough all fields and try to update them
        foreach ($fields as $k => $v) {
            // Ignore if the field is not empty
            if (! $isEmpty($k, $pageInfo)) {
                continue;
            }

            // Iterate up the root line
            foreach ($rootLine as $parentPageInfo) {
                // Load the row from the page info cache or from the repository
                if (! isset($pageInfoCache[$parentPageInfo['uid']])) {
                    $pageInfoCache[$parentPageInfo['uid']] = $pageService->getPageInfo($parentPageInfo['uid']);
                }
                $parentPageInfo = $pageInfoCache[$parentPageInfo['uid']];

                // Check if the field in the parent is empty as well
                if ($isEmpty($k, $parentPageInfo)) {
                    continue;
                }

                // Map the info
                $pageInfo[$k]                                         = $parentPageInfo[$k];
                $this->slideFieldPidMap[$k]                           = $parentPageInfo['pid'];
                $this->slideParentPageInfoMap[$parentPageInfo['pid']] = $parentPageInfo;
                continue 2;
            }
        }

        // Done
        return $pageInfo;
    }

    /**
     * Helper to apply slided properties to the generated model class
     * This is required because extbase does not have the concept of sliding, so we have
     * to manually resolve the slided data based on the parent records
     *
     * @param   string                                          $pageClass
     * @param   \TYPO3\CMS\Extbase\DomainObject\AbstractEntity  $model
     */
    protected function applySlideProperties(string $pageClass, AbstractEntity $model): void
    {
        $props          = $model->_getProperties();
        $slideableProps = array_combine(array_map([Inflector::class, 'toProperty'], array_keys($this->slideFieldPidMap)), $this->slideFieldPidMap);

        // Check if we have props to slide -> Ignore if not...
        $slidedProps = array_intersect_key($slideableProps, $props);
        if (empty($slidedProps)) {
            return;
        }

        // Store already created parent page models
        $parentModels = [];
        foreach ($slidedProps as $slidedProp => $parentPid) {
            // Try to resolve the model from the cache or create it
            if (! isset($parentModels[$parentPid])) {
                $parentModels[$parentPid] = $this->hydrateModelObject(
                    $pageClass, 'page', $this->slideParentPageInfoMap[$parentPid]);
            }
            $parent = $parentModels[$parentPid];
            $model->_setProperty($slidedProp, $parent->_getProperty($slidedProp));
        }
        unset($parentModels);
    }

    /**
     * Factory method to create a new instance of myself
     *
     * @param   int  $id
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(int $id): PageData
    {
        return TypoContainer::getInstance()->get(static::class, ['args' => [$id]]);
    }
}
