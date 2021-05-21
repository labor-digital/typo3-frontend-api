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
 * Last modified: 2019.08.19 at 17:17
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use Closure;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;

class TransformerProxy extends TransformerAbstract {
	
	/**
	 * The transformer instance we use internally
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer
	 */
	protected $transformer;
	
	/**
	 * Holds the data that is set before the transformer instance is created
	 * @var array
	 */
	protected $data = [];
	
	/**
	 * @var \Closure
	 */
	protected $instanceCreator;
	
	/**
	 * TransformerProxy constructor.
	 *
	 * @param \Closure $instanceCreator
	 */
	public function __construct(Closure $instanceCreator) {
		$this->instanceCreator = $instanceCreator;
	}
	
	/**
	 * Creates the real transformer instance and executes the transform method on it
	 *
	 * @param $value
	 *
	 * @return array
	 */
	public function transform($value): array {
		$this->transformer = call_user_func($this->instanceCreator, $value);
		$res = $this->transformer->transform($value);
		return $res;
	}
	
	/**
	 * Returns the correct transformer instance for the given value
	 *
	 * @param $value
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer
	 */
	public function getConcreteTransformer($value): AbstractResourceTransformer {
		return call_user_func($this->instanceCreator, $value);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getAvailableIncludes() {
		return $this->transformer->getAvailableIncludes();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getDefaultIncludes() {
		return $this->transformer->getDefaultIncludes();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getCurrentScope() {
		return $this->transformer->getCurrentScope();
	}
	
	/**
	 * @inheritDoc
	 */
	public function processIncludedResources(Scope $scope, $data) {
		return $this->transformer->processIncludedResources($scope, $data);
	}
	
	/**
	 * @inheritDoc
	 */
	public function setAvailableIncludes($availableIncludes) {
		return $this->transformer->setAvailableIncludes($availableIncludes);
	}
	
	/**
	 * @inheritDoc
	 */
	public function setDefaultIncludes($defaultIncludes) {
		return $this->transformer->setDefaultIncludes($defaultIncludes);
	}
	
	/**
	 * @inheritDoc
	 */
	public function setCurrentScope($currentScope) {
		$this->data["scope"] = $currentScope;
	}
}