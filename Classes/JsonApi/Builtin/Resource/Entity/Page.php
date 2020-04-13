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
 * Last modified: 2019.09.20 at 19:32
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\SiteConfigAwareTrait;
use LaborDigital\Typo3FrontendApi\Site\Configuration\RootLineDataProviderInterface;
use Neunerlei\Inflection\Inflector;

class Page {
	use SiteConfigAwareTrait {
		SiteConfigAwareTrait::getCommonElements as getCommonElementsInternal;
	}
	
	/**
	 * The page id we hold the representation for
	 * @var int
	 */
	protected $id;
	
	/**
	 * Holds the page layout identifier after it was resolved
	 * @var string|null
	 */
	protected $pageLayout;
	
	/**
	 * Holds the page's root line array after it was resolved
	 * @var array|null
	 */
	protected $rootLine;
	
	/**
	 * The list of loaded language codes the frontend already knows,
	 * this is used to avoid duplicate translations when translating this entity
	 * @var array
	 */
	protected $loadedLanguageCodes;
	
	/**
	 * An optional list of common element keys that should be included in the response.
	 * Useful if elements have to be refreshed on every page load.
	 * This is overwritten if the layout is changed because in that case all common elements will be rendered!
	 * @var array
	 */
	protected $refreshCommon;
	
	/**
	 * The last known layout of the frontend.
	 * This is used to check which common elements should be rendered.
	 * Common elements are only rendered if this layout does not match the page's layout
	 * @var string
	 */
	protected $lastLayout;
	
	/**
	 * Returns the page id this object represents
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}
	
	/**
	 * Returns the additional link entries for this page
	 * @return array
	 */
	public function getLinks(): array {
		// Prepare the link
		$link = $this->Links->getLink()->withPid($this->getId());
		return [
			"frontend" => $link->build(),
			"slug"     => $link->build(["relative"]),
		];
	}
	
	/**
	 * Returns the page layout identifier of the current page
	 * @return string
	 */
	public function getPageLayout(): string {
		if (!is_null($this->pageLayout)) return $this->pageLayout;
		$pageLayoutField = $this->getCurrentSiteConfig()->pageLayoutField;
		$pageData = $this->Page->getPageInfo($this->id);
		if (empty($pageData)) $pageData = [];
		// Find layout by root line if required
		if (!empty($pageData[$pageLayoutField]))
			$this->pageLayout = $pageData[$pageLayoutField];
		$rootLine = $this->Page->getRootLine($this->id);
		$lookupField = $pageLayoutField . "_next_level";
		foreach ($rootLine as $row) {
			if (!empty($row[$pageLayoutField]))
				return $this->pageLayout;
			if (!empty($row[$lookupField]))
				return $this->pageLayout = $row[$lookupField];
		}
		return $this->pageLayout = "default";
	}
	
	/**
	 * Returns the root line of this page as an array
	 * @return array
	 */
	public function getRootLine(): array {
		if (!is_null($this->rootLine)) return $this->rootLine;
		$rootLine = [];
		$rootLineRaw = $this->Page->getRootLine($this->id);
		$additionalFields = $this->getCurrentSiteConfig()->additionalRootLineFields;
		$dataProviders = $this->getCurrentSiteConfig()->rootLineDataProviders;
		$c = 0;
		foreach (array_reverse($rootLineRaw) as $pageData) {
			$pageDataPrepared = [
				"id"       => $pageData["uid"],
				"parentId" => $pageData["pid"],
				"level"    => $c++,
				"title"    => $pageData["title"],
				"navTitle" => $pageData["nav_title"],
				"slug"     => $this->Links->getLink()->withPid($pageData["uid"])->build(["relative"]),
			];
			
			// Merge in additional fields
			$pageInfo = NULL;
			if (!empty($additionalFields)) {
				$pageInfo = $this->Page->getPageInfo($pageDataPrepared["id"]);
				foreach ($additionalFields as $field) {
					$propertyName = Inflector::toCamelBack($field);
					if (isset($pageInfo[$field])) $pageDataPrepared["fields"][$propertyName] = $pageInfo[$field];
					else $pageDataPrepared["fields"][$propertyName] = NULL;
				}
			}
			
			// Check if we have data providers
			if (!empty($dataProviders)) {
				if (empty($pageInfo)) $pageInfo = $this->Page->getPageInfo($pageData["uid"]);
				foreach ($dataProviders as $dataProvider) {
					/** @var \LaborDigital\Typo3FrontendApi\Site\Configuration\RootLineDataProviderInterface $provider */
					$provider = $this->getInstanceOf($dataProvider);
					if (!$provider instanceof RootLineDataProviderInterface) continue;
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
	 * @return string
	 */
	public function getLanguageCode(): string {
		return $this->TypoContext->getLanguageAspect()->getCurrentFrontendLanguage()->getTwoLetterIsoCode();
	}
	
	/**
	 * Returns the list of all language codes the frontend may display
	 * @return array
	 */
	public function getLanguageCodes(): array {
		$languages = [];
		foreach ($this->TypoContext->getLanguageAspect()->getAllFrontendLanguages() as $language)
			$languages[] = $language->getTwoLetterIsoCode();
		return $languages;
	}
	
	/**
	 * Returns the list of all loaded language codes the frontend told us about
	 * @return array
	 */
	public function getLoadedLanguageCodes(): array {
		return $this->loadedLanguageCodes;
	}
	
	/**
	 * Returns the name of the last known layout the frontend told us about
	 * @return string
	 */
	public function getLastLayout(): string {
		return $this->lastLayout;
	}
	
	/**
	 * Returns true if we detected a layout change between the requests -> render all common elements
	 * @return bool
	 */
	public function isLayoutChange(): bool {
		return empty($this->getLastLayout()) || $this->getLastLayout() !== $this->getPageLayout();
	}
	
	/**
	 * Returns a list of common element keys that should be included in the response.
	 * @return array
	 */
	public function getRefreshCommon(): array {
		return $this->refreshCommon;
	}
	
	/**
	 * Returns the page data object for this page
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData
	 */
	public function getPageData(): PageData {
		return PageData::makeInstance($this->id);
	}
	
	/**
	 * Returns the content object list for this page
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageContent
	 */
	public function getPageContents(): PageContent {
		return PageContent::makeInstance($this->id);
	}
	
	/**
	 * Returns the list of typoScript and layout objects for this page
	 *
	 * @return array
	 */
	public function getCommonElements(): array {
		return $this->getCommonElementsInternal($this->getPageLayout(), ($this->isLayoutChange() ? [] : $this->getRefreshCommon()));
	}
	
	/**
	 * Returns the page translation object for the current frontend language of this page
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageTranslation
	 */
	public function getPageTranslation() {
		return PageTranslation::makeInstance($this->TypoContext->getLanguageAspect()->getCurrentFrontendLanguage());
	}
	
	/**
	 * Factory method to create a new instance of myself
	 *
	 * @param int    $pageId
	 * @param string $lastLayout
	 * @param array  $loadedLanguageCodes
	 *
	 * @param array  $refreshCommon
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page
	 */
	public static function makeInstance(int $pageId, string $lastLayout, array $loadedLanguageCodes, array $refreshCommon): Page {
		$self = TypoContainer::getInstance()->get(static::class);
		$self->id = $pageId;
		$self->lastLayout = $lastLayout;
		$self->loadedLanguageCodes = $loadedLanguageCodes;
		$self->refreshCommon = $refreshCommon;
		return $self;
	}
}