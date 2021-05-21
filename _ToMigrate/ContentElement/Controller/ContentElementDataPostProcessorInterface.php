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
 * Last modified: 2019.08.28 at 19:41
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Controller;


interface ContentElementDataPostProcessorInterface {
	
	/**
	 * Receives the cType of the current content element and should determine if this processor
	 * can handle the type or not.
	 *
	 * @param string $cType
	 *
	 * @return bool
	 */
	public function canHandle(string $cType): bool;
	
	/**
	 * Receives the data array that was returned by the controller and already merged with the global data list.
	 * The result should be the processed data array
	 *
	 * @param array                           $data
	 * @param ContentElementControllerContext $context
	 *
	 * @return array
	 */
	public function process(array $data, ContentElementControllerContext $context): array;
}