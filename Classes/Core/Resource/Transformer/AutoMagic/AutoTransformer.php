<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.06.09 at 15:16
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\Link\RteContentParser;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;
use Throwable;

class AutoTransformer
{
    use ContainerAwareTrait;
    
    protected const REFERENCE_MARKER_TPL = '{{AUTOMAGIC_AUTO_TRANSFORMER_CIRCULAR_REF_%s}}';
    
    /**
     * The currently active options
     *
     * @var array|null
     */
    protected $activeOptions;
    
    /**
     * The path through the nested values to detect recursions
     *
     * @var array
     */
    protected $path = [];
    
    /**
     * Handled circular transformation results
     *
     * @var array
     */
    protected $circularReferences = [];
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory
     */
    protected $transformerFactory;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory
     */
    protected $resourceFactory;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(
        TransformerFactory $transformerFactory,
        ResourceFactory $resourceFactory,
        TypoContext $typoContext
    )
    {
        $this->transformerFactory = $transformerFactory;
        $this->resourceFactory = $resourceFactory;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Can take virtually ANY value and convert it into a json compatible representation.
     *
     * @param   mixed       $value    The value to transform
     * @param   array|null  $options  Options for the transformation of objects. You can use the
     *                                options defined here {@Link AbstractResourceElement::asArray()}
     *                                Additionally: if $value is an array, you can define a special "byKey"
     *                                option which allows you to configure the options for each key of the
     *                                $value array separately. The options will be merged into the root level options.
     *                                This works ONLY for arrays not for iterable objects, tho!
     *
     * @return array|bool|float|int|mixed|string|null
     */
    public function transform($value, ?array $options = null)
    {
        $value = AutoTransformUtil::unifyValue($value);
        
        $isRoot = $this->activeOptions === null;
        $pathBackup = $this->path;
        $optionBackup = $this->activeOptions;
        $this->activeOptions = $options ?? [];
        
        try {
            $isScalar = is_scalar($value);
            
            if (! $isScalar && in_array($value, $this->path, true)) {
                $circular = $this->handleCircularTransformation($value);
                
                // The $circular value is a string with the reference marker if it got replaced
                // If an array was wrongly detected it will be the array, so we check here again
                if (is_string($circular)) {
                    return $circular;
                }
                
                $value = $circular;
                unset($circular);
            }
            
            if (! $isScalar) {
                $referenceKey = microtime(true) . mt_rand();
                $this->path[$referenceKey] = $value;
            }
            
            if (! empty($options)) {
                $this->activeOptions = $options;
            }
            
            $transformed = $this->transformValue($value);
            
            // Check if a reference key exists and it has been back-referenced somewhere
            if (isset($referenceKey) && ($this->circularReferences[$referenceKey] ?? null) === true) {
                $transformed = $this->replaceCircularReferences($transformed, $referenceKey);
            }
            
            return $transformed;
        } finally {
            $this->activeOptions = $optionBackup;
            $this->path = $pathBackup;
            
            if ($isRoot) {
                $this->circularReferences = [];
            }
        }
    }
    
    /**
     * Allows reliable transformation of link references inside of string values.
     * It will convert all TYPO3 link definitions into a real link
     *
     * @param   string  $input
     *
     * @return string
     */
    public function transformLinkReferences(string $input): string
    {
        if (empty($input) || is_numeric($input)) {
            return $input;
        }
        
        if (stripos($input, 't3://') === 0 && strip_tags($input) === $input) {
            return $this->cs()->links->getTypoLink($input);
        }
        
        if (stripos($input, 't3://') !== false) {
            return $this->getService(RteContentParser::class)->parseContent($input);
        }
        
        return $input;
    }
    
    /**
     * Internal helper that does the actual transformation after the class has been set up correctly.
     *
     * @param   mixed  $value
     *
     * @return array|bool|float|int|mixed|string|null
     */
    protected function transformValue($value)
    {
        if (is_string($value)) {
            return $this->transformLinkReferences($value);
        }
        
        if (is_array($value)) {
            return $this->transformArrayValue($value);
        }
        
        if (is_object($value)) {
            return $this->transformObjectValue($value);
        }
        
        if (! is_scalar($value)) {
            try {
                return SerializerUtil::unserializeJson(SerializerUtil::serializeJson($value));
            } catch (Throwable $e) {
                return '[' . gettype($value) . ']';
            }
        }
        
        return $value;
    }
    
    /**
     * Internal helper to execute the transformation of array values key by key
     *
     * @param   array  $value
     *
     * @return array
     */
    protected function transformArrayValue(array $value): array
    {
        $options = $this->activeOptions;
        
        $result = [];
        foreach ($value as $k => $v) {
            // The "byKey" options will override the options set on a global level.
            $localOptions
                = isset($options['byKey'][$k]) && is_array($options['byKey'][$k]) ?
                array_merge($options, $options['byKey'][$k]) : $options;
            unset($localOptions['byKey']);
            
            $result[$k] = $this->transform($v, $localOptions);
        }
        
        return $result;
    }
    
    /**
     * Internal helper to execute the conversion of objects through the resource transformer
     *
     * @param   object  $value
     *
     * @return array|mixed|null
     */
    protected function transformObjectValue(object $value)
    {
        $options = $this->activeOptions;
        
        if ($this->transformerFactory->hasValueTransformer($value)) {
            return $this->transformerFactory->getTransformer($value)->transform($value);
        }
        
        if ($value instanceof ResourceItem || $value instanceof ResourceCollection) {
            $res = $value;
        } elseif ($this->typoContext->resource()->isCollection($value) && is_iterable($value)) {
            /** @noinspection PhpParamsInspection */
            $res = $this->resourceFactory->makeResourceCollection($value);
        } else {
            $res = $this->resourceFactory->makeResourceItem($value);
        }
        
        unset($options['byKey']);
        $transformed = $res->asArray($options);
        
        // It "feels" better to automatically unwrap the "data" when we are not explicitly
        // are requiring the results of a json api
        if (isset($transformed['data']) && count($transformed) === 1
            && ! (($options['jsonApi'] ?? null) === true || in_array('jsonApi', $options, true))) {
            $transformed = $transformed['data'];
        }
        
        // If we got a transformed value that ONLY contains an id that is a string AND we used the
        // default transformer to transform it -> we drop the transformed value by replacing it with null
        if (is_string($transformed['id']) &&
            ! $this->transformerFactory->hasNonDefaultTransformer($value)
            && count($transformed) === 1) {
            $transformed = null;
        }
        
        return $transformed;
    }
    
    /**
     * Handles the events when a circular transformation occurs.
     * This happens if classes contain references on themself.
     *
     * @param $value
     *
     * @return mixed|string
     */
    protected function handleCircularTransformation($value)
    {
        $referenceKey = array_search($value, $this->path, true);
        $isCircular = true;
        
        if (is_array($value)) {
            // Check if the array is really a recursion or only looks like one
            if ($referenceKey !== false) {
                $testKey = sha1(microtime(true) . random_int(0, 9999999));
                $value[$testKey] = true;
                $isCircular = $this->path[$referenceKey][$testKey] === true;
                unset($value[$testKey]);
            } else {
                $isCircular = false;
            }
        }
        
        if (! $isCircular) {
            return $value;
        }
        
        // @todo implement TransformerCircularDependencyFilterEvent
        
        $this->circularReferences[$referenceKey] = true;
        
        return sprintf(static::REFERENCE_MARKER_TPL, $referenceKey);
    }
    
    /**
     * Tries to inject circular references by a bit of black string magic back into the transformed value
     *
     * @param   mixed   $transformedValue
     * @param   string  $referenceKey
     *
     * @return mixed|null
     */
    protected function replaceCircularReferences($transformedValue, string $referenceKey)
    {
        unset($this->circularReferences[$referenceKey]);
        
        $marker = '"' . sprintf(static::REFERENCE_MARKER_TPL, $referenceKey) . '"';
        $transformedString = SerializerUtil::serializeJson($transformedValue);
        $referenceString = str_replace($marker, '"[RECURSION]"', $transformedString);
        $transformedString = str_replace($marker, $referenceString, $transformedString);
        
        return SerializerUtil::unserializeJson($transformedString);
    }
}