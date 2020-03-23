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
 * Last modified: 2019.08.08 at 10:44
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


class TransformerConfig {
	
	/**
	 * The class name of the transformer we should create
	 * @var string
	 */
	public $transformerClass;
	
	/**
	 * Either null or the found resource type for this value
	 * @var null|string
	 */
	public $resourceType = NULL;
	
	/**
	 * Holds the resource configuration for this transformer config
	 * @var null|\LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig
	 */
	public $resourceConfig = NULL;
	
	/**
	 * If true: Let the object translate itself
	 * @var bool
	 */
	public $isSelfTransforming = FALSE;
	
	/**
	 * If true: Translate this to null
	 * @var bool
	 */
	public $isNull = FALSE;
	
	/**
	 * If true: Translate this as scalar value
	 * @var bool
	 */
	public $isScalar = FALSE;
	
	/**
	 * If True: translate this as array value
	 * @var bool
	 */
	public $isArray = FALSE;
	
	/**
	 * Properties that can be included
	 * @var array
	 */
	public $includes = [];
	
	/**
	 * Attributes and their getters
	 * @var array
	 */
	public $attributes = [];
	
	/**
	 * The id getter method
	 * @var \Closure
	 */
	public $idGetter;
	
	/**
	 * The list of post processors, wrapped in closures to allow lazy instantiation without
	 * injecting the container into the transformer
	 * @var callable[]
	 */
	public $postProcessors = [];
}