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
 * Last modified: 2019.11.25 at 21:50
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


/**
 * Interface HybridSelfTransformingInterface
 *
 * If your self transforming entity implements this interface instead
 * of the normal self transforming interface the transformer will assume that your
 * transformation is only the first step. It will then automatically send the result of the "toArray"
 * method into the autoTransform() method to transform nested elements you did not handle yourself
 *
 * @package LaborDigital\Typo3FrontendApi\JsonApi\Transformation
 */
interface HybridSelfTransformingInterface extends SelfTransformingInterface {
	
}