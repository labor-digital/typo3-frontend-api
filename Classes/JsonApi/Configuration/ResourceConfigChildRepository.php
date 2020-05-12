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
 * Last modified: 2019.08.26 at 17:57
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Configuration;


use LaborDigital\Typo3BetterApi\LazyLoading\LazyLoadingTrait;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigChildRepositoryInterface;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\Arrays;

class ResourceConfigChildRepository implements FrontendApiConfigChildRepositoryInterface {
	use LazyLoadingTrait;
	
	/**
	 * The parent repository
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $parent;
	
	/**
	 * Tries to find the matching resource type for the given value.
	 * If the value is not an object, or no matching resource type was found the method will return null
	 *
	 * @param object|mixed $value The value to get the resource type for
	 * @param bool         $returnFallback
	 *
	 * @return string|null
	 */
	public function getResourceTypeByValue($value, bool $returnFallback = TRUE): ?string {
		if (!is_object($value)) {
			if (is_string($value) && class_exists($value)) {
				$type = $this->getResourceTypeByClassName($value, FALSE);
				if (!empty($type) || !$returnFallback) return $type;
			}
			return $returnFallback ? $this->makeResourceType(gettype($value)) : NULL;
		}
		$value = $this->lazyLoading->getRealValue($value);
		$type = $this->getResourceTypeByClassName(get_class($value), FALSE);
		if (empty($type)) {
			if (is_iterable($value))
				foreach ($value as $_v) {
					if (!is_object($_v)) break;
					$type = $this->getResourceTypeByClassName(get_class($_v), FALSE);
					break;
				}
		}
		return empty($type) && $returnFallback ? $this->makeResourceType(get_class($value)) : $type;
	}
	
	/**
	 * Can be used to resolve a resource type based on a class name
	 *
	 * @param string $className
	 * @param bool   $returnFallback
	 *
	 * @return string|null
	 */
	public function getResourceTypeByClassName(string $className, bool $returnFallback = FALSE): ?string {
		if (empty($className) || !class_exists($className))
			return $returnFallback ? $this->makeResourceType($className) : NULL;
		
		// Try direct lookup
		$classMap = $this->getResourceConfigMap()->classResourceMap;
		$type = NULL;
		$resourceType = $classMap[$className];
		if (!empty($resourceType)) $type = $resourceType;
		
		// Try to find the parents
		else {
			$possibleClasses = Arrays::attach([$className], array_values(class_parents($className)));
			foreach ($possibleClasses as $testClassName) {
				$resourceType = $classMap[$testClassName];
				if (!is_null($resourceType)) {
					$type = $resourceType;
					break;
				}
			}
		}
		return empty($type) && $returnFallback ? $this->makeResourceType($className) : $type;
	}
	
	
	/**
	 * Tries to find the configuration array of a resource type.
	 * If the resource type was not found and the class name is given we will try to resolve the
	 * resource type using the class name
	 *
	 * @param string|null $resourceType
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig|null
	 */
	public function getResourceConfig(string $resourceType): ?ResourceConfig {
		$result = $this->getResourceConfigMap()->resources[(string)$resourceType];
		if (empty($result)) return NULL;
		return $result;
	}
	
	/**
	 * Checks if the given string matches a registered resource type.
	 *
	 * @param string $resourceType
	 *
	 * @return bool
	 */
	public function isResourceType(string $resourceType): bool {
		return !empty($this->getResourceConfig($resourceType));
	}
	
	/**
	 * Returns all registered resources
	 * @return array
	 */
	public function getAll(): array {
		return $this->getResourceConfigMap()->resources;
	}
	
	/**
	 * Returns either the special object transformer class for the given object class or null
	 * if there was none registered for the object type
	 *
	 * @param string $class The name of the class to get the transformer for
	 *
	 * @return string|null
	 */
	public function getSpecialObjectTransformerFor(string $class): ?string {
		$transformers = $this->parent->getConfiguration("specialObjectTransformers");
		if (!is_array($transformers)) return NULL;
		if (!class_exists($class)) return NULL;
		foreach (Arrays::attach([$class], class_parents($class), class_implements($class)) as $class)
			if (isset($transformers[$class])) return $transformers[$class];
		return NULL;
	}
	
	/**
	 * Internal helper to create a dummy resource type for not configured objects
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	protected function makeResourceType(string $type = "unknown"): string {
		$parts = array_filter(explode("\\", $type));
		$parts = array_map("ucfirst", array_filter(["auto", array_shift($parts), array_pop($parts)]));
		return lcfirst(implode($parts));
	}
	
	/**
	 * Internal helper to get the resource map from the parent configuration object
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigMap
	 */
	protected function getResourceConfigMap(): ResourceConfigMap {
		return $this->parent->getConfiguration("resource");
	}
	
	/**
	 * @inheritDoc
	 */
	public function __setParentRepository(FrontendApiConfigRepository $parent): void {
		$this->parent = $parent;
	}
}