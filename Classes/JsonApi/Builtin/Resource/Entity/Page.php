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
 * Last modified: 2019.09.20 at 19:32
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Event\PageRootLineFilterEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Translation\PageTranslation;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use LaborDigital\Typo3FrontendApi\Site\Configuration\RootLineDataProviderInterface;
use Neunerlei\Inflection\Inflector;

class Page
{
    use FrontendApiContextAwareTrait;

    /**
     * The page id we hold the representation for
     *
     * @var int
     */
    protected $id;

    /**
     * Holds the page layout identifier after it was resolved
     *
     * @var string|null
     */
    protected $pageLayout;

    /**
     * Holds the page's root line array after it was resolved
     *
     * @var array|null
     */
    protected $rootLine;

    /**
     * The list of loaded language codes the frontend already knows,
     * this is used to avoid duplicate translations when translating this entity
     *
     * @var array
     */
    protected $loadedLanguageCodes;

    /**
     * An optional list of common element keys that should be included in the response.
     * Useful if elements have to be refreshed on every page load.
     * This is overwritten if the layout is changed because in that case all common elements will be rendered!
     *
     * @var array
     */
    protected $refreshCommon;

    /**
     * The two char iso language code for this element
     *
     * @var string
     */
    protected $languageCode;

    /**
     * The last known layout of the frontend.
     * This is used to check which common elements should be rendered.
     * Common elements are only rendered if this layout does not match the page's layout
     *
     * @var string
     */
    protected $lastLayout;

    /**
     * Page constructor.
     *
     * @param   int     $id
     * @param   string  $lastLayout
     * @param   array   $loadedLanguageCodes
     * @param   array   $refreshCommon
     * @param   string  $languageCode
     */
    public function __construct(int $id, string $lastLayout, array $loadedLanguageCodes, array $refreshCommon, string $languageCode)
    {
        $this->id                  = $id;
        $this->lastLayout          = $lastLayout;
        $this->loadedLanguageCodes = $loadedLanguageCodes;
        $this->refreshCommon       = $refreshCommon;
        $this->languageCode        = $languageCode;
    }

    /**
     * Returns the page id this object represents
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the additional link entries for this page
     *
     * @return array
     */
    public function getLinks(): array
    {
        // Find the Page links
        $pageLinks = $this->FrontendApiContext()->getInstanceWithoutDi(
            PageLinks::class, [$this->id]
        )->asArray();

        // Prepare the link
        $link = $this->FrontendApiContext()->Links()->getLink()->withPid($this->getId());

        return array_merge($pageLinks, [
            'frontend' => $link->build(),
            // @todo rename slug to "relative" in v10
            'slug'     => $link->build(['relative']),
        ]);
    }

    /**
     * Returns the base url for the site that contains this page
     *
     * @return string
     */
    public function getSiteUrl(): string
    {
        return $this->FrontendApiContext()->TypoContext()->Site()->getCurrent()->getBase()->__toString();
    }

    /**
     * Returns the page layout identifier of the current page
     *
     * @return string
     */
    public function getPageLayout(): string
    {
        if ($this->pageLayout !== null) {
            return $this->pageLayout;
        }
        $context         = $this->FrontendApiContext();
        $pageLayoutField = $context->getCurrentSiteConfig()->pageLayoutField;
        $pageData        = $context->Page()->getPageInfo($this->id);
        if (empty($pageData)) {
            $pageData = [];
        }

        // Find layout by root line if required
        if (! empty($pageData[$pageLayoutField])) {
            return $this->pageLayout = $pageData[$pageLayoutField];
        }
        $rootLine    = $context->Page()->getRootLine($this->id);
        $lookupField = $pageLayoutField . '_next_level';
        foreach ($rootLine as $row) {
            if (! empty($row[$pageLayoutField])) {
                return $this->pageLayout;
            }
            if (! empty($row[$lookupField])) {
                return $this->pageLayout = $row[$lookupField];
            }
        }

        return $this->pageLayout = 'default';
    }

    /**
     * Returns the root line of this page as an array
     *
     * @return array
     */
    public function getRootLine(): array
    {
        if ($this->rootLine !== null) {
            return $this->rootLine;
        }

        $context        = $this->FrontendApiContext();
        $rootLine       = [];
        $rootLineRaw    = $context->Page()->getRootLine($this->id);
        $this->rootLine = $rootLineRaw;

        // Allow filtering
        $context->EventBus()->dispatch(($e = new PageRootLineFilterEvent($this, $rootLineRaw)));
        $this->rootLine = null;
        $rootLineRaw    = $e->getRootLine();

        // Traverse the root line and inject the additional data
        $siteConfig       = $context->getCurrentSiteConfig();
        $additionalFields = $siteConfig->additionalRootLineFields;
        $dataProviders    = $siteConfig->rootLineDataProviders;
        $c                = 0;
        foreach (array_reverse($rootLineRaw) as $pageData) {
            $pageDataPrepared = [
                'id'       => $pageData['uid'],
                'parentId' => $pageData['pid'],
                'level'    => $c++,
                'title'    => $pageData['title'],
                'navTitle' => $pageData['nav_title'],
                'link'     => $context->Links()->getLink()->withPid($pageData['uid'])->build(['relative']),
            ];

            // @todo remove this in v10
            $pageDataPrepared['slug'] = $pageDataPrepared['link'];

            // Merge in additional fields
            $pageInfo = null;
            if (! empty($additionalFields)) {
                $pageInfo = $context->Page()->getPageInfo($pageDataPrepared['id']);
                foreach ($additionalFields as $field) {
                    $propertyName = Inflector::toCamelBack($field);
                    if (isset($pageInfo[$field])) {
                        $pageDataPrepared['fields'][$propertyName] = $pageInfo[$field];
                    } else {
                        $pageDataPrepared['fields'][$propertyName] = null;
                    }
                }
            }

            // Check if we have data providers
            if (! empty($dataProviders)) {
                if (empty($pageInfo)) {
                    $pageInfo = $context->Page()->getPageInfo($pageData['uid']);
                }
                foreach ($dataProviders as $dataProvider) {
                    /** @var \LaborDigital\Typo3FrontendApi\Site\Configuration\RootLineDataProviderInterface $provider */
                    $provider = $context->getInstanceOf($dataProvider);
                    if (! $provider instanceof RootLineDataProviderInterface) {
                        continue;
                    }
                    $pageDataPrepared = $provider->addData($pageDataPrepared, $pageInfo, $rootLineRaw);
                }
            }

            // Done
            $rootLine[] = $pageDataPrepared;
        }

        return $this->rootLine = $rootLine;
    }

    /**
     * Returns the current language code of this page
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * Returns the list of all language codes the frontend may display
     *
     * @return array
     */
    public function getLanguageCodes(): array
    {
        $languages = [];
        foreach ($this->FrontendApiContext()->TypoContext()->Language()->getAllFrontendLanguages() as $language) {
            $languages[] = $language->getTwoLetterIsoCode();
        }

        return $languages;
    }

    /**
     * Returns the list of all loaded language codes the frontend told us about
     *
     * @return array
     */
    public function getLoadedLanguageCodes(): array
    {
        return $this->loadedLanguageCodes;
    }

    /**
     * Returns the name of the last known layout the frontend told us about
     *
     * @return string
     */
    public function getLastLayout(): string
    {
        return $this->lastLayout;
    }

    /**
     * Returns true if we detected a layout change between the requests -> render all common elements
     *
     * @return bool
     */
    public function isLayoutChange(): bool
    {
        return empty($this->getLastLayout()) || $this->getLastLayout() !== $this->getPageLayout();
    }

    /**
     * Returns a list of common element keys that should be included in the response.
     *
     * @return array
     */
    public function getRefreshCommon(): array
    {
        return $this->refreshCommon;
    }

    /**
     * Returns the list of pids that are configured for this page
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PagePidConfig
     */
    public function getPagePidConfig(): PagePidConfig
    {
        return $this->FrontendApiContext()->getInstanceWithoutDi(PagePidConfig::class, [$this->id]);
    }

    /**
     * Returns the page data object for this page
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData
     */
    public function getPageData(): PageData
    {
        return $this->FrontendApiContext()->getInstanceWithoutDi(PageData::class, [$this->id, $this->languageCode]);
    }

    /**
     * Returns the content object list for this page
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageContent
     */
    public function getPageContents(): PageContent
    {
        return $this->FrontendApiContext()->getInstanceWithoutDi(PageContent::class, [$this->id, $this->languageCode]);
    }

    /**
     * Returns the list of typoScript and layout objects for this page
     *
     * @return array
     */
    public function getCommonElements(): array
    {
        return $this->FrontendApiContext()->getCurrentSiteConfig()->getCommonElementInstances(
            $this->getPageLayout(),
            $this->isLayoutChange() ? [] : $this->getRefreshCommon()
        );
    }

    /**
     * Returns the page translation object for the current frontend language of this page
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Translation\PageTranslation
     */
    public function getPageTranslation(): PageTranslation
    {
        return $this->FrontendApiContext()->getInstanceWithoutDi(PageTranslation::class, [$this->languageCode]);
    }

    /**
     * Factory method to create a new instance of myself
     *
     * @param   int     $pageId
     * @param   string  $lastLayout
     * @param   array   $loadedLanguageCodes
     * @param   array   $refreshCommon
     * @param   string  $languageCode
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(int $pageId, string $lastLayout, array $loadedLanguageCodes, array $refreshCommon, string $languageCode): Page
    {
        return TypoContainer::getInstance()->get(static::class, [
            'args' => [$pageId, $lastLayout, $loadedLanguageCodes, $refreshCommon, $languageCode],
        ]);
    }
}
