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
 * Last modified: 2019.08.11 at 12:58
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use DateTime;
use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3BetterApi\Link\TypoLink;
use LaborDigital\Typo3FrontendApi\Event\ResourceTransformerPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\ResourceTransformerPreProcessorEvent;
use LaborDigital\Typo3FrontendApi\Shared\Adapter\DummyRenderingContext;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Neunerlei\Options\Options;
use Neunerlei\TinyTimy\DateTimy;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlViewHelper;

abstract class AbstractResourceTransformer extends TransformerAbstract {
	use ResourceTransformerTrait;
	use CommonServiceLocatorTrait;
	
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
	 * Receives the value and should convert it into an array
	 *
	 * It behaves exactly as Fractal's "transform" method would but we fill that with additional overhead,
	 * so we had to adjust the naming a bit. See fractal's transformer documentation for additional info
	 *
	 * @param mixed $value
	 *
	 * @return array
	 * @see https://fractal.thephpleague.com/transformers/
	 */
	abstract protected function transformValue($value): array;
	
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
	 * Helper to create the "include" collection when building your own transformer
	 *
	 * @param $value
	 *
	 * @return \League\Fractal\Resource\Collection
	 */
	protected function autoIncludeCollection($value): Collection {
		return $this->collection($value,
			$this->transformerFactory->getTransformer()->getConcreteTransformer($value),
			$this->transformerFactory->getConfigFor($value)->resourceType);
	}
	
	/**
	 * Helper to create the "include" item when building your own transformer
	 *
	 * @param $value
	 *
	 * @return \League\Fractal\Resource\Item
	 */
	protected function autoIncludeItem($value): Item {
		return $this->item($value,
			$this->transformerFactory->getTransformer()->getConcreteTransformer($value),
			$this->transformerFactory->getConfigFor($value)->resourceType);
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
					// Handle date objects
					if ($value instanceof DateTime)
						return (new DateTimy($value))->formatJs();
					
					// Handle link objects
					if ($value instanceof TypoLink)
						return $value->build();
					if ($value instanceof UriInterface)
						return (string)$value;
					if ($value instanceof UriBuilder)
						return $value->buildFrontendUri();
					
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
		} else if (!empty($v) && is_string($v) && stripos($v, "t3://") !== FALSE) {
			// Hijack the html viewHelper to render the links of our content
			return HtmlViewHelper::renderStatic(["parseFuncTSPath" => "lib.parseFunc_RTE"], function () use ($input) {
				return $input;
			}, $this->getInstanceOf(DummyRenderingContext::class));
		} else return $input;
	}
	
	/**
	 * This method hooks into the processor of fractal and adds our additional,
	 * generic overhead to it we want to apply to all called transformers
	 *
	 * @param $value
	 *
	 * @return array
	 */
	final public function transform($value) {
		// Enable additional processing
		$this->EventBus->dispatch(($e = new ResourceTransformerPreProcessorEvent($value, $this->config)));
		$this->config = $e->getConfig();
		$value = $e->getValue();
		
		// Transform the item
		$result = $this->transformValue($value);
		$result = json_decode(json_encode($result), TRUE);
		
		// Check if there are registered post processors
		if (!empty($this->config->postProcessors))
			foreach ($this->config->postProcessors as $postProcessor)
				$result = call_user_func($postProcessor, $result, $value, $this->config->resourceType, $this->config->resourceConfig);
		
		// Apply check for allowed and denied properties
		if (!empty($this->config->resourceConfig)) {
			$hasAllowed = !empty($this->config->resourceConfig->allowedProperties);
			$hasDenied = !empty($this->config->resourceConfig->deniedProperties);
			if ($hasAllowed || $hasDenied) {
				$filtered = [];
				foreach ($result as $k => $v) {
					// Always include the id property
					if ($k !== "id") {
						// Check if the property is allowed
						if ($hasAllowed && !in_array($k, $this->config->resourceConfig->allowedProperties)) continue;
						
						// Check if the property is denied
						if ($hasDenied && in_array($k, $this->config->resourceConfig->deniedProperties)) continue;
					}
					
					// Add to filtered list
					$filtered[$k] = $v;
				}
				$result = $filtered;
			}
		}
		
		// Enable additional processing
		$this->EventBus->dispatch(($e = new ResourceTransformerPostProcessorEvent($result, $value, $this->config)));
		return $e->getResult();
	}
}