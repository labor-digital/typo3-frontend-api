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
 * Last modified: 2019.08.08 at 08:57
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


class Transformer extends AbstractResourceTransformer {
	
	/**
	 * @inheritDoc
	 */
	protected function transformValue($value): array {
		// Select handling type
		if ($this->config->isArray) {
			// Handle array
			$result = $this->autoTransform($value);
		} else if ($this->config->isScalar) {
			// Handle scalar
			$result = [
				"id"    => md5(microtime(TRUE)),
				"value" => $value,
			];
		} else if ($this->config->isNull) {
			$result = ["id" => NULL];
		} else if ($this->config->isSelfTransforming && $value instanceof SelfTransformingInterface) {
			$result = $value->asArray();
			if ($value instanceof HybridSelfTransformingInterface)
				$result = $this->autoTransform($value);
		} else {
			// Handle objects
			$this->setAvailableIncludes(array_keys($this->config->includes));
			$result = [
				"id" => call_user_func($this->config->idGetter, $value),
			];
			
			// Transform the attributes
			foreach ($this->config->attributes as $k => $getterClosure)
				$result[$k] = $this->autoTransform(call_user_func($getterClosure, $value));
		}
		
		// Done
		return $result;
	}
	
	/**
	 * Magic method to automatically handle the include method calls
	 *
	 * @param $name
	 * @param $arguments
	 *
	 * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\Item|null|\League\Fractal\Resource\NullResource
	 */
	public function __call($name, $arguments) {
		// Check if this is a registered property
		$property = lcfirst(substr($name, 7));
		if (!isset($this->config->includes[$property])) return $this->null();
		$value = $arguments[0];
		
		// Load the config and the data
		$config = $this->config->includes[$property];
		$data = call_user_func($this->config->includes[$property]["getter"], $value);
		
		// Run the transformer
		if (empty($data)) return $this->null();
		$childConfig = $this->transformerFactory->getConfigFor($data);
		$childTransformer = $this->transformerFactory->getTransformer();
		if ($config["isCollection"]) return $this->collection($data, $childTransformer, $childConfig->resourceType);
		return $this->item($data, $childTransformer, $childConfig->resourceType);
	}
}