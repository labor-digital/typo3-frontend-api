<?php
/**
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
 * Last modified: 2020.04.13 at 23:12
 */

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page;

/**
 * Class PageRootLineFilterEvent
 * Emitted when the Page object build's its root line.
 * It is used to filter the raw data that was retrieved from TYPO3 for the current page
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class PageRootLineFilterEvent {
	
	/**
	 * The page object that currently tries to resolve it's root line
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page
	 */
	protected $page;
	
	/**
	 * The raw root line array TYPO3 responded for this page
	 * @var array
	 */
	protected $rootLine;
	
	/**
	 * PageRootLineFilterEvent constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page $page
	 * @param array                                                               $rootLine
	 */
	public function __construct(Page $page, array $rootLine) {
		$this->page = $page;
		$this->rootLine = $rootLine;
	}
	
	/**
	 * Returns the page object that currently tries to resolve it's root line
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page
	 */
	public function getPage(): Page {
		return $this->page;
	}
	
	/**
	 * Returns the raw root line array TYPO3 responded for this page
	 * @return array
	 */
	public function getRootLine(): array {
		return $this->rootLine;
	}
	
	/**
	 * Updates the raw root line array TYPO3 responded for this page
	 *
	 * @param array $rootLine
	 *
	 * @return PageRootLineFilterEvent
	 */
	public function setRootLine(array $rootLine): PageRootLineFilterEvent {
		$this->rootLine = $rootLine;
		return $this;
	}
	
	
}