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
 * Last modified: 2019.08.20 at 13:26
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Normalizer;

interface NormalizerInterface {
	/**
	 * Receives the serialized json api structure and normalizes it into a tree of structured data.
	 * The linked relationships will automatically be resolved as properties of each entity.
	 *
	 * @param array|\stdClass $response
	 *
	 * @return array
	 */
	public function normalize($response);
}