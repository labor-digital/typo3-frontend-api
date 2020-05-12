<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.05.11 at 23:17
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use Iterator;
use LaborDigital\Typo3BetterApi\Container\CommonServiceDependencyTrait;
use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use Neunerlei\Options\Options;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

trait TransformerTrait {
	use CommonServiceLocatorTrait;
	use CommonServiceDependencyTrait {
		CommonServiceDependencyTrait::getInstanceOf insteadof CommonServiceLocatorTrait;
		CommonServiceDependencyTrait::injectContainer insteadof CommonServiceLocatorTrait;
	}
	
	/**
	 * Max number of tracked values that can be auto-transformed.
	 * If this number is exceeded an exception will be thrown
	 * @var int
	 */
	public static $maxAutoTransformDepth = 50;
	
	/**
	 * The transformer configuration
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	protected $config;
	
	/**
	 * Reference to the transformer factory object to create child transformers with
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
	 */
	protected $transformerFactory;
	
	/**
	 * Is used by the transformer factory to inject the correct config array for the current transformation
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig $config
	 *
	 * @return $this
	 */
	public function setTransformerConfig(TransformerConfig $config) {
		$this->config = $config;
		return $this;
	}
	
	/**
	 * Returns the instance of the transformer's configuration object
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	public function getTransformerConfig(): TransformerConfig {
		return $this->config;
	}
	
	/**
	 * Is used by the factory to inject itself into the instance
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory $factory
	 *
	 * @return $this
	 */
	public function setFactory(TransformerFactory $factory) {
		$this->transformerFactory = $factory;
		return $this;
	}
	
	/**
	 * This helper can be used to automatically transform a certain value.
	 * It will handle arrays recursively, convert datetime and link objects on the fly and also call
	 * other transformers to convert complex objects.
	 *
	 * @param mixed $value   The value to transform
	 * @param array $options Additional config options
	 *                       - callNestedTransformer bool (TRUE): By default the method will automatically call other
	 *                       transformers to convert complex objects. If you don't want that to happen, set this
	 *                       argument to false. When doing so the method will simply return complex objects it could
	 *                       not transform on it's own.
	 *                       - allIncludes bool (FALSE): If this is set to true all children of the value
	 *                       will be included in the transformed output. By default the auto transformer
	 *                       will ignore includes.
	 *
	 * @return mixed
	 * @throws \Throwable
	 */
	protected function autoTransform($value, array $options = []) {
		// Prepare options
		if (empty($options["@recursion"])) {
			$options = Options::make($options, [
				"@recursion"            => [
					"default" => TRUE,
				],
				"callNestedTransformer" => [
					"default" => TRUE,
					"type"    => "bool",
				],
				"allIncludes"           => [
					"default" => FALSE,
					"type"    => "bool",
				],
			]);
		}
		
		// Track values to avoid circular transformation that would lead to a never ending loop
		$isTrackedValue = FALSE;
		if (is_object($value) || is_array($value)) {
			$isTrackedValue = TRUE;
			if (in_array($value, AutoTransformContext::$path, TRUE) ||
				count(AutoTransformContext::$path) > static::$maxAutoTransformDepth) {
				$isCircular = TRUE;
				// Check if a known array definition is indeed a circular reference
				if (is_array($value)) {
					$key = array_search($value, AutoTransformContext::$path);
					if ($key !== FALSE) {
						$tempKey = sha1(microtime(TRUE) . rand(0, 9999999));
						$value[$tempKey] = TRUE;
						$isCircular = AutoTransformContext::$path[$key][$tempKey] === TRUE;
						unset($value[$tempKey]);
					} else if (count(AutoTransformContext::$path) <= static::$maxAutoTransformDepth) {
						$isCircular = FALSE;
					}
				}
				if ($isCircular) {
					// Try to use the auto-transformer to transform an object
					// Which is already the last object in the list
					// This probably means that someone created his own transformer class just to do
					// custom includes or wants to have the scaffold entity data already transformed
					// by the auto-transformer
					if (
						end(AutoTransformContext::$path) === $value &&
						$this->config->transformerClass !== Transformer::class) {
						$transformerClassBackup = $this->config->transformerClass;
						try {
							$this->config->transformerClass = Transformer::class;
							$autoTransformer = $this->transformerFactory
								->getTransformer($this->config->resourceType)
								->getConcreteTransformer($value);
							return $autoTransformer->transform($value);
						} finally {
							$this->config->transformerClass = $transformerClassBackup;
						}
					}
					
					// No, nothing we can do here...
					throw TransformationException::makeNew("Found a circular transformation: " .
						implode(" -> ", AutoTransformContext::$path), $value);
				}
			};
			array_push(AutoTransformContext::$path, $value);
		}
		
		try {
			$result = (function ($value) use ($options) {
				// Handle links in strings
				if (!empty($value) && is_string($value) && !is_numeric($value))
					$value = $this->transformTypoLinkStringReferences($value);
				
				// Handle arrays
				if (is_array($value)) {
					$result = [];
					foreach ($value as $k => $v)
						$result[$k] = $this->autoTransform($v, $options);
					return $result;
				}
				
				// Handle objects
				if (is_object($value)) {
					// Transform special objects if possible
					$valueTransformerConfig = $this->transformerFactory->getConfigFor($value);
					if ($valueTransformerConfig->specialObjectTransformerClass !== NULL)
						return $this->transformerFactory->getTransformer()->transform($value)["value"];
					
					// Make sure object storage objects end up as array and not as object...
					if ($value instanceof ObjectStorage)
						return $this->autoTransform(array_values($value->toArray()), $options);
					
					// Handle iterable objects
					if (is_iterable($value)) {
						$result = [];
						/** @noinspection PhpWrongForeachArgumentTypeInspection */
						foreach ($value as $k => $v)
							$result[$k] = $this->autoTransform($v, $options);
						return $result;
					}
					
					// Handle remaining objects
					if ($options["callNestedTransformer"]) {
						if ($options["allIncludes"]) {
							// Do the heavy lifting and load all includes...
							$transformer = $this->transformerFactory->getTransformer()->getConcreteTransformer($value);
							$result = $transformer->transform($value);
							foreach ($transformer->getTransformerConfig()->includes as $k => $include)
								$result[$k] = $this->autoTransform($include["getter"]($value), $options);
							return $result;
						}
						return $this->transformerFactory->getTransformer()->transform($value);
					}
				}
				
				return $value;
			})($value);
			
			// Done
			return $result;
		} finally {
			if ($isTrackedValue) array_pop(AutoTransformContext::$path);
		}
	}
	
	/**
	 * This internal helper is used to convert all typo3 link definitions into a real link, either
	 * as a simple url or as parsed string containing <a> tags.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	protected function transformTypoLinkStringReferences(string $input): string {
		// Ignore empty strings or numbers
		if (empty($input) || is_numeric($input)) return $input;
		// Check if the value is a simple url string
		if (stripos($input, "t3://") === 0 && strip_tags($input) == $input) {
			// Simple url handling
			return $this->Links->getTypoLink($input);
		} else if (!empty($input) && is_string($input) && stripos($input, "t3://") !== FALSE) {
			return $this->getInstanceOf(RteContentParser::class)->parseContent($input);
		} else return $input;
	}
	
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