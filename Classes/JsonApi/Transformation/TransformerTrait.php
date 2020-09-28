<?php
declare(strict_types=1);
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
use LaborDigital\Typo3BetterApi\Container\CommonDependencyTrait;
use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use Neunerlei\Options\Options;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

trait TransformerTrait
{
    use FrontendApiContextAwareTrait;
    use CommonServiceLocatorTrait;

    /**
     * @deprecated This trait will be removed from the transformer trait in v10
     */
    use CommonDependencyTrait {
        CommonDependencyTrait::getInstanceOf insteadof CommonServiceLocatorTrait;
        CommonDependencyTrait::injectContainer insteadof CommonServiceLocatorTrait;
    }

    /**
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
     * @deprecated temporary, will be removed in v10
     */
    private $_transformerFactory;

    /**
     * Max number of tracked values that can be auto-transformed.
     * If this number is exceeded an exception will be thrown
     *
     * @var int
     */
    public static $maxAutoTransformDepth = 50;

    /**
     * The transformer configuration
     *
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
     */
    protected $config;

    /**
     * Is used by the transformer factory to inject the correct config array for the current transformation
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig  $config
     *
     * @return $this
     */
    public function setTransformerConfig(TransformerConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Returns the instance of the transformer's configuration object
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
     */
    public function getTransformerConfig(): TransformerConfig
    {
        return $this->config;
    }

    /**
     * Is used by the factory to inject itself into the instance
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory  $factory
     *
     * @return $this
     * @deprecated will be removed in v10
     */
    public function setFactory(TransformerFactory $factory): self
    {
        $this->_transformerFactory = $factory;

        return $this;
    }

    /**
     * Returns the instance of the transformer factory
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
     */
    protected function TransformerFactory(): TransformerFactory
    {
        // @todo remove this in v10
        if (isset($this->_transformerFactory)) {
            return $this->_transformerFactory;
        }

        return $this->FrontendApiContext()->TransformerFactory();
    }

    /**
     * This helper can be used to automatically transform a certain value.
     * It will handle arrays recursively, convert datetime and link objects on the fly and also call
     * other transformers to convert complex objects.
     *
     * @param   mixed  $value    The value to transform
     * @param   array  $options  Additional config options
     *                           - callNestedTransformer bool (TRUE): By default the method will automatically call other
     *                           transformers to convert complex objects. If you don't want that to happen, set this
     *                           argument to false. When doing so the method will simply return complex objects it could
     *                           not transform on it's own.
     *                           - allIncludes bool (FALSE): If this is set to true all children of the value
     *                           will be included in the transformed output. By default the auto transformer
     *                           will ignore includes.
     *
     * @return mixed
     * @throws \Throwable
     */
    protected function autoTransform($value, array $options = [])
    {
        // Prepare options
        if (empty($options['@recursion'])) {
            $options = Options::make($options, [
                '@recursion'            => [
                    'default' => true,
                ],
                'callNestedTransformer' => [
                    'default' => true,
                    'type'    => 'bool',
                ],
                'allIncludes'           => [
                    'default' => false,
                    'type'    => 'bool',
                ],
            ]);
        }

        // Track values to avoid circular transformation that would lead to a never ending loop
        $isTrackedValue = false;
        $isArrayValue   = is_array($value);
        if (is_object($value) || $isArrayValue) {
            $isTrackedValue = true;
            if (in_array($value, AutoTransformContext::$path, true) ||
                count(AutoTransformContext::$path) > static::$maxAutoTransformDepth) {
                $isCircular = true;
                // Check if a known array definition is indeed a circular reference
                if ($isArrayValue) {
                    $key = array_search($value, AutoTransformContext::$path, true);
                    if ($key !== false) {
                        $tempKey         = sha1(microtime(true) . random_int(0, 9999999));
                        $value[$tempKey] = true;
                        $isCircular      = AutoTransformContext::$path[$key][$tempKey] === true;
                        unset($value[$tempKey]);
                    } elseif (count(AutoTransformContext::$path) <= static::$maxAutoTransformDepth) {
                        $isCircular = false;
                    }
                }
                if ($isCircular) {
                    // Try to use the auto-transformer to transform an object
                    // Which is already the last object in the list
                    // This probably means that someone created his own transformer class just to do
                    // custom includes or wants to have the scaffold entity data already transformed
                    // by the auto-transformer
                    if (
                        $this->config->transformerClass !== Transformer::class &&
                        end(AutoTransformContext::$path) === $value) {
                        $transformerClassBackup = $this->config->transformerClass;
                        try {
                            $this->config->transformerClass = Transformer::class;
                            $autoTransformer                = $this->TransformerFactory()
                                                                   ->getTransformer($this->config->resourceType)
                                                                   ->getConcreteTransformer($value);

                            return $autoTransformer->transform($value);
                        } finally {
                            $this->config->transformerClass = $transformerClassBackup;
                        }
                    }

                    // No, nothing we can do here...
                    throw TransformationException::makeNew(
                        'Found a circular transformation: ' .
                        implode(' -> ', AutoTransformContext::$path), $value);
                }
            }
            AutoTransformContext::$path[] = $value;
        }

        try {
            return (function ($value) use ($options) {
                // Handle links in strings
                if (! empty($value) && is_string($value) && ! is_numeric($value)) {
                    $value = $this->transformTypoLinkStringReferences($value);
                }

                // Handle arrays
                if (is_array($value)) {
                    $result = [];
                    foreach ($value as $k => $v) {
                        $result[$k] = $this->autoTransform($v, $options);
                    }

                    return $result;
                }

                // Handle objects
                if (is_object($value)) {
                    $factory = $this->TransformerFactory();

                    // Transform special objects if possible
                    $valueTransformerConfig = $factory->getConfigFor($value);
                    if ($valueTransformerConfig->specialObjectTransformerClass !== null) {
                        return $factory->getTransformer()->transform($value)['value'];
                    }

                    // Make sure object storage objects end up as array and not as object...
                    if ($value instanceof ObjectStorage) {
                        return $this->autoTransform(array_values($value->toArray()), $options);
                    }

                    // Handle iterable objects
                    if (is_iterable($value)) {
                        $result = [];
                        /** @noinspection PhpWrongForeachArgumentTypeInspection */
                        foreach ($value as $k => $v) {
                            $result[$k] = $this->autoTransform($v, $options);
                        }

                        return $result;
                    }

                    // Handle remaining objects
                    if ($options['callNestedTransformer']) {
                        if ($options['allIncludes']) {
                            // Do the heavy lifting and load all includes...
                            $transformer = $factory->getTransformer()->getConcreteTransformer($value);
                            $result      = $transformer->transform($value);
                            foreach ($transformer->getTransformerConfig()->includes as $k => $include) {
                                $result[$k] = $this->autoTransform($include['getter']($value), $options);
                            }

                            return $result;
                        }

                        return $factory->getTransformer()->transform($value);
                    }
                }

                return $value;
            })($value);
        } finally {
            if ($isTrackedValue) {
                array_pop(AutoTransformContext::$path);
            }
        }
    }

    /**
     * This internal helper is used to convert all typo3 link definitions into a real link, either
     * as a simple url or as parsed string containing <a> tags.
     *
     * @param   string  $input
     *
     * @return string
     */
    protected function transformTypoLinkStringReferences(string $input): string
    {
        // Ignore empty strings or numbers
        if (empty($input) || is_numeric($input)) {
            return $input;
        }
        // Check if the value is a simple url string
        if (stripos($input, 't3://') === 0 && strip_tags($input) === $input) {
            // Simple url handling
            return $this->Links()->getTypoLink($input);
        }

        if (! empty($input) && is_string($input) && stripos($input, 't3://') !== false) {
            return $this->getInstanceOf(RteContentParser::class)->parseContent($input);
        }

        return $input;
    }

    /**
     * Tries to return the first element of an array, or an iterable object
     * Otherwise the given value will be returned again
     *
     * @param   mixed  $list  The list to get the first value of
     *
     * @return array|mixed
     * @deprecated will be removed in v10
     */
    protected function getFirstOfList($list)
    {
        if (is_array($list)) {
            return reset($list);
        }
        if (is_object($list)) {
            if ($list instanceof Iterator) {
                foreach ($list as $v) {
                    return $v;
                }
            }
            $list = get_object_vars($list);

            return reset($list);
        }

        return $list;
    }

    /**
     * @inheritDoc
     * @deprecated only for legacy compatibility until v10
     */
    public function __get($name)
    {
        if ($name === 'transformerFactory') {
            return $this->TransformerFactory();
        }

        return null;
    }

    /**
     * @param $name
     * @param $value
     *
     * @deprecated only for legacy compatibility until v10
     */
    public function __set($name, $value)
    {
        if ($name === 'transformerFactory') {
            $this->_transformerFactory = $value;
        }
    }
}
