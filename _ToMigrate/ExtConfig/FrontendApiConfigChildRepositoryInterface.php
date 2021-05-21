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
 * Last modified: 2020.01.16 at 17:04
 */

namespace LaborDigital\Typo3FrontendApi\ExtConfig;


interface FrontendApiConfigChildRepositoryInterface {
	
	/**
	 * Injects the root config repository into the repository class that implements this interface
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $parent
	 */
	public function __setParentRepository(FrontendApiConfigRepository $parent): void;
}