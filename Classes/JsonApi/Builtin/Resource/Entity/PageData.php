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
 * Last modified: 2019.09.20 at 18:42
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Event\PageDataPageInfoFilterEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\SiteConfigAwareTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\Shared\ModelHydrationTrait;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class PageData {
	use SiteConfigAwareTrait;
	use ModelHydrationTrait;
	
	/**
	 * The page id we hold the data for
	 * @var int
	 */
	protected $id;
	
	/**
	 * Holds the last page info array or is null
	 * @var array
	 */
	protected $pageInfo;
	
	/**
	 * The map of fields and their parent page uids to map references correctly
	 * @var array
	 */
	protected $slideFieldPidMap = [];
	
	/**
	 * The list of all parent page info objects for slided fields to create the slided model with
	 * @var array
	 */
	protected $slideParentPageInfoMap = [];
	
	/**
	 * Returns the page data representation as an object
	 * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
	 * @throws \League\Route\Http\Exception\NotFoundException
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function getData(): AbstractEntity {
		$pageClass = $this->getCurrentSiteConfig()->pageDataClass;
		if (!class_exists($pageClass)) throw new JsonApiException("The given page data class: $pageClass does not exist!");
		$model = $this->hydrateModelObject($pageClass, "pages", $this->getPageInfo());
		
		// Check if we have to update slided properties
		if (!empty($this->slideFieldPidMap))
			$this->applySlideProperties($pageClass, $model);
		
		// Done
		return $model;
	}
	
	/**
	 * Returns the raw page database row
	 * @return array
	 * @throws \League\Route\Http\Exception\NotFoundException
	 */
	public function getPageInfo(): array {
		if (isset($this->pageInfo)) return $this->pageInfo;
		$pageInfo = $this->Page->getPageInfo($this->id);
		if (empty($pageInfo)) throw new NotFoundException();
		
		// Apply slide fields if required
		$slideFields = $this->getCurrentSiteConfig()->pageDataSlideFields;
		if (!empty($slideFields)) $pageInfo = $this->applySlideFields($pageInfo, $slideFields);
		
		// Allow filtering
		$this->EventBus->dispatch(($e = new PageDataPageInfoFilterEvent($this->id, $pageInfo, $this->slideFieldPidMap)));
		return $this->pageInfo = $e->getRow();
	}
	
	/**
	 * Returns the map of fields and their parent page uids to map references correctly
	 * @return array
	 */
	public function getSlideFieldPidMap(): array {
		if (!isset($this->pageInfo)) $this->getPageInfo();
		return $this->slideFieldPidMap;
	}
	
	/**
	 * Returns the list of all parent page info objects for slided fields to create the slided model with
	 * @return array
	 */
	public function getSlideParentPageInfoMap(): array {
		return $this->slideParentPageInfoMap;
	}
	
	/**
	 * Factory method to create a new instance of myself
	 *
	 * @param int $id
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData
	 */
	public static function makeInstance(int $id): PageData {
		$self = TypoContainer::getInstance()->get(static::class);
		$self->id = $id;
		return $self;
	}
	
	/**
	 * Helper to apply the slide fields on the raw database data to inherit data from the parent pages
	 *
	 * @param array $pageInfo
	 * @param array $slideFields
	 *
	 * @return array
	 */
	protected function applySlideFields(array $pageInfo, array $slideFields): array {
		// The list of all fields that can be slided (filled by the parent page)
		$fields = array_intersect_key($pageInfo, array_fill_keys($slideFields, NULL));
		
		// A helper to check if a field's value is empty
		$isEmpty = function (string $field, array $pageInfo): bool {
			if (!array_key_exists($field, $pageInfo)) return FALSE;
			if (is_null($pageInfo[$field]) || $pageInfo[$field] === "" || $pageInfo[$field] === "0") return TRUE;
			return FALSE;
		};
		
		// Generate the root line and prepare a cache to store resolved page information
		$rootLine = $this->Page->getRootLine($this->id);
		$pageInfoCache = [];
		
		// Run trough all fields and try to update them
		foreach ($fields as $k => $v) {
			
			// Ignore if the field is not empty
			if (!$isEmpty($k, $pageInfo)) continue;
			
			// Iterate up the root line
			foreach ($rootLine as $parentPageInfo) {
				// Load the row from the page info cache or from the repository
				if (!isset($pageInfoCache[$parentPageInfo["uid"]]))
					$pageInfoCache[$parentPageInfo["uid"]] = $this->Page->getPageInfo($parentPageInfo["uid"]);
				$parentPageInfo = $pageInfoCache[$parentPageInfo["uid"]];
				
				// Check if the field in the parent is empty as well
				if ($isEmpty($k, $parentPageInfo)) continue;
				
				// Map the info
				$pageInfo[$k] = $parentPageInfo[$k];
				$this->slideFieldPidMap[$k] = $parentPageInfo["pid"];
				$this->slideParentPageInfoMap[$parentPageInfo["pid"]] = $parentPageInfo;
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
	 * @param string                                         $pageClass
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $model
	 */
	protected function applySlideProperties(string $pageClass, AbstractEntity $model): void {
		$props = $model->_getProperties();
		$slideableProps = array_combine(array_map([Inflector::class, "toProperty"], array_keys($this->slideFieldPidMap)), $this->slideFieldPidMap);
		
		// Check if we have props to slide -> Ignore if not...
		$slidedProps = array_intersect_key($slideableProps, $props);
		if (empty($slidedProps)) return;
		
		// Store already created parent page models
		$parentModels = [];
		foreach ($slidedProps as $slidedProp => $parentPid) {
			// Try to resolve the model from the cache or create it
			if (!isset($parentModels[$parentPid]))
				$parentModels[$parentPid] = $this->hydrateModelObject(
					$pageClass, "page", $this->slideParentPageInfoMap[$parentPid]);
			/** @var AbstractEntity $parent */
			$parent = $parentModels[$parentPid];
			$model->_setProperty($slidedProp, $parent->_getProperty($slidedProp));
		}
		unset($parentModels);
	}
}