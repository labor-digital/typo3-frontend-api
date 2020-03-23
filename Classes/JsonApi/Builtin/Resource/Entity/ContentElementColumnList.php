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
 * Last modified: 2019.09.26 at 18:18
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;

class ContentElementColumnList implements SelfTransformingInterface {
	/**
	 * True while we recursively convert the children into a json api array
	 * @var bool
	 */
	protected $asJsonApiArray = FALSE;
	
	/**
	 * The list of columns this list holds
	 * COL_ID => ELEMENTS[]
	 * @var array
	 */
	public $columns = [];
	
	/**
	 * @inheritDoc
	 */
	public function asArray(): array {
		$result = [];
		foreach ($this->columns as $colId => $columnElements)
			foreach ($columnElements as $columnElement)
				$result[$colId][] = $this->asJsonApiArray ?
					$columnElement->asJsonApiArray() : $columnElement->asArray();
		return $result;
	}
	
	/**
	 * Similar to "asArray" but returns the content elements formatted in the json-api schema
	 * @return array
	 */
	public function asJsonApiArray(): array {
		$this->asJsonApiArray = TRUE;
		$result = $this->asArray();
		$this->asJsonApiArray = FALSE;
		return $result;
	}
	
	/**
	 * Factory method to create a new instance of myself based on the result of the page service's getPageContents()
	 * method
	 *
	 * @param array $contents
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElementColumnList
	 * @see \LaborDigital\Typo3BetterApi\Page\PageService::getPageContents()
	 */
	public static function makeInstanceFromPageContentsArray(array $contents): ContentElementColumnList {
		$self = TypoContainer::getInstance()->get(static::class);
		foreach ($contents as $colId => $columnElements) {
			foreach ($columnElements as $columnElement) {
				$element = ContentElement::makeInstanceElementWithAutomaticPopulation($columnElement["uid"]);
				if (!empty($columnElement["children"]))
					$element->children = ContentElementColumnList::makeInstanceFromPageContentsArray($columnElement["children"]);
				$self->columns[$colId][] = $element;
			}
		}
		return $self;
	}
}