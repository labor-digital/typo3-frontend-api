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
 * Last modified: 2019.08.28 at 14:17
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Domain\Repository;


use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\DefaultContentElementModel;
use LaborDigital\Typo3FrontendApi\Domain\Table\Override\TtContentOverrides;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class ContentElementRepository {
	use CommonServiceLocatorTrait;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;
	
	/**
	 * @var \TYPO3\CMS\Core\Service\FlexFormService
	 */
	protected $flexFormService;
	
	/**
	 * ContentElementRepository constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper             $dataMapper
	 * @param \TYPO3\CMS\Core\Service\FlexFormService                              $flexFormService
	 */
	public function __construct(FrontendApiConfigRepository $configRepository, DataMapper $dataMapper, FlexFormService $flexFormService) {
		$this->configRepository = $configRepository;
		$this->dataMapper = $dataMapper;
		$this->flexFormService = $flexFormService;
	}
	
	/**
	 * Receives a row of the tt_content table and tries to map it into a ext base content element model.
	 * The model class is resolved using the typoScript of the content element and the TCA array.
	 *
	 * @param array $row The database row to convert into a model instance
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel
	 * @throws \LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException
	 */
	public function hydrateRow(array $row): AbstractContentElementModel {
		$cType = Arrays::getPath($row, ["CType"]);
		if (empty($cType)) throw new ContentElementException("The given row could not be hydrated, as it does not specify a CType field!");
		$modelClass = $this->configRepository->contentElement()->getContentElementConfig($cType, "modelClass");
		if (empty($modelClass)) $modelClass = DefaultContentElementModel::class;
		
		// Unpack the virtual columns
		$virtualColumns = $this->configRepository->contentElement()->getContentElementConfig($cType, "virtualColumns");
		if (!empty($virtualColumns) && !empty($row[TtContentOverrides::VIRTUAL_COLUMN_FIELD])) {
			$virtualColumnValues = Arrays::makeFromJson($row[TtContentOverrides::VIRTUAL_COLUMN_FIELD]);
			$row = Arrays::merge($row, $virtualColumnValues);
		}
		unset($row[TtContentOverrides::VIRTUAL_COLUMN_FIELD]);
		
		// Map the row to the model
		$mapped = $this->dataMapper->map($modelClass, [$row]);
		/** @var \LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel $mapped */
		$mapped = reset($mapped);
		$mapped->_setProperty("__raw", $row);
		$mapped->_setProperty("__virtualColumnMap", $virtualColumns);
		
		// Unpack flex form fields
		$colConfig = $GLOBALS["TCA"]["tt_content"]["columns"];
		$flexCols = [];
		foreach ($colConfig as $col => $conf) {
			// Check if this is a flex form column
			if (empty($row[$col])) continue;
			$type = Arrays::getPath($colConfig, [$col, "config", "type"]);
			if ($type !== "flex") continue;
			
			// Parse the content
			$value = $row[$col];
			$value = $this->flexFormService->convertFlexFormContentToArray($value);
			
			// Make sure to translate virtual columns
			$realCol = array_search($col, $virtualColumns);
			if (!$realCol) $realCol = $col;
			$realCol = Inflector::toProperty($realCol);
			
			// Store the column
			$flexCols[$realCol] = $value;
		}
		$mapped->_setProperty("__flex", $flexCols);
		
		// Done
		return $mapped;
	}
}