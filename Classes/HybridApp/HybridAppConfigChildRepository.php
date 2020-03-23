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
 * Last modified: 2019.12.10 at 20:18
 */

namespace LaborDigital\Typo3FrontendApi\HybridApp;


use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigChildRepositoryInterface;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\Arrays;

class HybridAppConfigChildRepository implements FrontendApiConfigChildRepositoryInterface {
	
	/**
	 * The parent repository
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $parent;
	
	/**
	 * Returns all registered translation files
	 * @return array
	 */
	public function getTranslationFiles(): array {
		return Arrays::getPath($this->parent->getConfiguration("hybrid"), ["translationFiles"], []);
	}
	
	/**
	 * Returns the global data array
	 * @return array
	 */
	public function getGlobalData(): array {
		return Arrays::getPath($this->parent->getConfiguration("hybrid"), ["globalData"], []);
	}
	
	/**
	 * Returns the list of registered global data post processor classes
	 * @return array
	 */
	public function getGlobalDataPostProcessors(): array {
		return Arrays::getPath($this->parent->getConfiguration("hybrid"), ["dataPostProcessors"], []);
	}
	
	/**
	 * Returns the currently configured window variable name where the global data and the translation
	 * labels will be stored initially.
	 * @return string
	 */
	public function getWindowVarName(): string {
		return Arrays::getPath($this->parent->getConfiguration("hybrid"), ["windowVarName"], "FRONTEND_API_DATA");
	}
	
	/**
	 * @inheritDoc
	 */
	public function __setParentRepository(FrontendApiConfigRepository $parent): void {
		$this->parent = $parent;
	}
	
}