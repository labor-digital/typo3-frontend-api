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
 * Last modified: 2021.05.20 at 00:06
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\Link\RteContentParser;
use LaborDigital\T3fa\Core\Resource\Transformer\Schema\SchemaRegistry;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;
use Neunerlei\Arrays\Arrays;
use Throwable;

class AutoTransformer
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory
     */
    protected $factory;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Schema\SchemaRegistry
     */
    protected $registry;
    
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
    
    public function __construct(TransformerFactory $factory, SchemaRegistry $registry)
    {
        $this->factory = $factory;
        $this->registry = $registry;
    }
    
    public function transform($value, ?array $options = null)
    {
        $clearOptions = $this->prepareOptions($options);
        
        try {
            if (is_string($value)) {
                return $this->transformLinkReferences($value);
            }
            
            // @todo iterable objects?
            if (is_array($value)) {
                $result = [];
                foreach ($value as $k => $v) {
                    $result[$k] = $this->transform($v, $options);
                }
                
                return $result;
            }
            
            if (is_object($value)) {
                if (! $this->factory->hasNonDefaultTransformer($value) && is_iterable($value)) {
                    $result = [];
                    foreach ($value as $k => $v) {
                        $result[$k] = $this->transform($v);
                    }
                    
                    return $result;
                }
                
                $transformer = $this->factory->getTransformer($value);
                $result = $transformer->transform($value);
                
                if ($this->activeOptions['allIncludes']) {
                    $includes = $transformer->getAvailableIncludes();
                    $schema = $this->registry->getSchema($value);
                }
                
                // @todo handle child includes
                
                return $result;
            }
            
            if (! is_scalar($value)) {
                try {
                    return Arrays::makeFromJson(Arrays::dumpToJson($value));
                } catch (Throwable $e) {
                    return '[' . gettype($value) . ']';
                }
            }
            
            return $value;
        } finally {
            if ($clearOptions) {
                $this->activeOptions = null;
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
     * @todo relative url handling
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
    
    protected function prepareOptions(?array $options): bool
    {
        if (isset($this->activeOptions)) {
            return false;
        }
        
        $this->activeOptions = $options ?? [];
        
        return true;
    }
    
    protected function transformValue($value)
    {
    }
}