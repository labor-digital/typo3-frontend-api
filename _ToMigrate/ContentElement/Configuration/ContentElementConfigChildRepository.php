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
 * Last modified: 2019.08.27 at 10:16
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration;


use LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigChildRepositoryInterface;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\Arrays;

class ContentElementConfigChildRepository implements FrontendApiConfigChildRepositoryInterface {
	
	/**
	 * The parent repository
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $parent;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService
	 */
	protected $typoScriptService;
	
	/**
	 * The mapping of the content element's and their virtual columns
	 * @var array
	 */
	protected $virtualColumnMap;
	
	/**
	 * A list of all virtual columns, of all elements combined
	 * @var array
	 */
	protected $virtualColumnList;
	
	/**
	 * A list of the content element configuration extracted from the typo script array for faster lookups
	 * @var array
	 */
	protected $configCache = [];
	
	/**
	 * ContentElementConfigRepository constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService $typoScriptService
	 */
	public function __construct(TypoScriptService $typoScriptService) {
		$this->typoScriptService = $typoScriptService;
	}
	
	/**
	 * Returns either the full configuration of a content element (done by typoScript) or just a single property
	 * of said configuration array.
	 *
	 * @param string      $cType    The content element's cType to find the configuration for
	 * @param string|null $property An optional property to narrow down the full configuration set
	 *
	 * @return array|mixed|null
	 */
	public function getContentElementConfig(string $cType, ?string $property = NULL) {
		$config = $this->lookUpElementConfig($cType);
		if (empty($property)) return $config;
		return Arrays::getPath($config, [$property]);
	}
	
	/**
	 * Returns a list of all virtual columns that are registered on the tt_content table.
	 *
	 * @return array
	 */
	public function getAllVirtualColumns(): array {
		if (is_array($this->virtualColumnList)) return $this->virtualColumnList;
		$list = [];
		foreach ($this->getVirtualColumnMap() as $columns)
			foreach ($columns as $colName => $vColumn)
				$list[$vColumn] = $colName;
		return $this->virtualColumnList = $list;
	}
	
	/**
	 * Finds the virtual columns for a specific content element in the tt_content TCA array and returns it.
	 * If there are no virtual columns for the cType an empty array is returned.
	 *
	 * @param string $cType The content element's cType to find the virtual columns for
	 *
	 * @return array
	 */
	public function getVirtualColumnsFor(string $cType): array {
		$map = $this->getVirtualColumnMap();
		return Arrays::getPath($map, [$cType], []);
	}
	
	/**
	 * Returns the virtual column map for all existing content elements
	 * @return array
	 */
	public function getVirtualColumnMap(): array {
		if (!is_null($this->virtualColumnMap)) return $this->virtualColumnMap;
		return $this->virtualColumnMap = Arrays::getPath($GLOBALS,
			["TCA", "tt_content", "additionalConfig", "virtualColumns"], []);
	}
	
	/**
	 * Returns the list of css classes that should be provided to all content elements
	 * @return array
	 */
	public function getGlobalCssClasses(): array {
		return $this->parent->getConfiguration("contentElement")["globalCssClasses"];
	}
	
	/**
	 * Returns the list of data post processor classes that are currently registered
	 * @return array
	 */
	public function getDataPostProcessors(): array {
		return array_unique($this->parent->getConfiguration("contentElement")["dataPostProcessors"]);
	}
	
	/**
	 * Internal helper that is used to get the configuration array for a certain cType.
	 * It will cache the looked up value to save performance.
	 *
	 * @param string $cType
	 *
	 * @return array
	 */
	protected function lookUpElementConfig(string $cType): array {
		if (isset($this->configCache[$cType])) return $this->configCache[$cType];
		$config = $this->typoScriptService->get(["tt_content", $cType], []);
		$config = $this->typoScriptService->removeDots($config);
		$config["virtualColumns"] = $this->getVirtualColumnsFor($cType);
		return $this->configCache[$cType] = $config;
	}
	
	/**
	 * @inheritDoc
	 */
	public function __setParentRepository(FrontendApiConfigRepository $parent): void {
		$this->parent = $parent;
	}
	
}