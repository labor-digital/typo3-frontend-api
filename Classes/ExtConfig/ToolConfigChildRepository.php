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
 * Last modified: 2020.01.20 at 16:55
 */

namespace LaborDigital\Typo3FrontendApi\ExtConfig;


use Neunerlei\Arrays\Arrays;

class ToolConfigChildRepository implements FrontendApiConfigChildRepositoryInterface {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $parent;
	
	/**
	 * Returns the configured value at the given path or returns the given default value when the path was not found
	 *
	 * @param string|array $path    Either the path separated by "." or an array of path segments
	 * @param null|mixed   $default The default value to return if the path was not found
	 *
	 * @return array|mixed|null
	 */
	public function get($path, $default = NULL) {
		return Arrays::getPath($this->parent->getConfiguration("tool"), $path, $default);
	}
	
	/**
	 * @inheritDoc
	 */
	public function __setParentRepository(FrontendApiConfigRepository $parent): void {
		$this->parent = $parent;
	}
	
}