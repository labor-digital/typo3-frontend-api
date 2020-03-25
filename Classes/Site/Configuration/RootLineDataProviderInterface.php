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
 * Last modified: 2020.03.25 at 14:32
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


interface RootLineDataProviderInterface {
	
	/**
	 * Used to add additional, dynamic data to your root line entries.
	 * The method is called once for every step in the root line
	 *
	 * @param array $rootLineEntry The currently prepared entry of the root line
	 * @param array $pageInfo      The row of from the pages table that provides the root line entry
	 * @param array $rawRootLine   The raw root line as array
	 *
	 * @return array Should return the updated $rootLineEntry with it's data filtered
	 */
	public function addData(array $rootLineEntry, array $pageInfo, array $rawRootLine): array;
}