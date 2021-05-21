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
 * Last modified: 2019.11.10 at 16:10
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;

class TransformationException extends JsonApiException {
	protected $value;
	
	public function getValue() {
		return $this->value;
	}
	
	public static function makeNew(string $message, $value): TransformationException {
		$e = new TransformationException($message);
		$e->value = $value;
		return $e;
	}
}