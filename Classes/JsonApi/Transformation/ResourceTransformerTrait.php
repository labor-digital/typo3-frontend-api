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
 * Last modified: 2019.08.13 at 14:56
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use Iterator;

trait ResourceTransformerTrait {
	
	/**
	 * Tries to return the first element of an array, or an iterable object
	 * Otherwise the given value will be returned again
	 *
	 * @param mixed $list The list to get the first value of
	 *
	 * @return array|mixed
	 */
	protected function getFirstOfList($list) {
		if (is_array($list)) return reset($list);
		if (is_object($list)) {
			if ($list instanceof Iterator)
				foreach ($list as $v)
					return $v;
			$list = get_object_vars($list);
			return reset($list);
		}
		return $list;
	}
	
}