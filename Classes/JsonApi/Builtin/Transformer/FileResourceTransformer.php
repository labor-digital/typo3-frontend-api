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
 * Last modified: 2019.08.11 at 12:37
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer;


use LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;
use Throwable;

class FileResourceTransformer extends AbstractResourceTransformer {
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService
	 */
	protected $falFileService;
	
	public function __construct(FalFileService $falFileService) {
		$this->falFileService = $falFileService;
	}
	
	public function transformValue($value): array {
		if (empty($value)) return ["id" => NULL];
		try {
			return $this->falFileService->getFileInformation($value);
		} catch (Throwable $e) {
			// Make sure that a missing file reference does not crash the entire page
			return ["id" => NULL, "error" => "Failed to gather the file information!"];
		}
	}
}