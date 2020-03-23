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
 * Last modified: 2019.08.12 at 22:42
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation\PostProcessing;


use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig;

interface ResourcePostProcessorInterface {
	
	/**
	 * Receives the already transformed resource as an array, the original value and the resource information
	 * and should do additional transformation for this specific resource type.
	 *
	 * Useful for adding additional fields to existing transformers
	 *
	 * @param array               $result         The transformed version of $value
	 * @param mixed               $value          The original value that was transformed
	 * @param string|null         $resourceType   The resource type or null if it is not known
	 * @param ResourceConfig|null $resourceConfig The resource configuration or null if it is not known
	 *
	 * @return array Should return the modified result
	 */
	public function process(array $result, $value, ?string $resourceType, ?ResourceConfig $resourceConfig): array;
	
}