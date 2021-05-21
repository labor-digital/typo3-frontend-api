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
 * Last modified: 2021.05.19 at 21:53
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Internal;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Transformer\Implementation\DefaultResourceTransformer;
use LaborDigital\T3fa\Core\Resource\Transformer\Implementation\SelfTransformer;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;
use LaborDigital\T3fa\TypoContext\SiteConfigAwareTrait;

class ClassResolver
{
    use ContainerAwareTrait;
    use SiteConfigAwareTrait;
    
    /**
     * A list of class name and their resolved transformer classes
     *
     * @var array
     */
    protected $resolvedTransformers = [];
    
    /**
     * A list of post processor classes that have been resolved for a certain class name
     *
     * @var array
     */
    protected $resolvedPostProcessors = [];
    
    public function __construct(TypoContext $context)
    {
        $this->context = $context;
        $this->registerConfig('t3fa.transformer');
    }
    
    /**
     * Resolves the correct transformer class for the given value
     *
     * @param   mixed  $value                    The value to find the transformer for
     * @param   bool   $includeValueTransformer  Determines if only resource transformers should be returned,
     *                                           If set to true, a value transformer is a valid option, too.
     *
     * @return string
     */
    public function getTransformerClass($value, bool $includeValueTransformer): string
    {
        $transformerClass = DefaultResourceTransformer::class;
        
        if (is_object($value)) {
            if ($value instanceof SelfTransformingInterface) {
                return SelfTransformer::class;
            }
            
            $class = get_class($value);
            $key = $class . '.' . $this->getSiteIdentifier() . '.' . ($includeValueTransformer ? '1' : '0');
            
            if (isset($this->resolvedTransformers[$key])) {
                return $this->resolvedTransformers[$key];
            }
            
            $config = $this->getSiteConfig();
            $valueTransformers = $config['value'] ?? [];
            $resourceTransformers = $config['resource'] ?? [];
            
            $foundValueTransformer = $valueTransformers[$class] ?? null;
            $foundResourceTransformer = $resourceTransformers[$class] ?? null;
            
            // Resolve based on parents
            if ($foundResourceTransformer === null && $foundValueTransformer === null) {
                foreach (class_parents($class) as $parent) {
                    if ($includeValueTransformer && isset($valueTransformers[$parent])) {
                        $foundValueTransformer = $valueTransformers[$parent];
                        break;
                    }
                    
                    if (isset($resourceTransformers[$parent])) {
                        $foundResourceTransformer = $resourceTransformers[$parent];
                        
                        if (! $includeValueTransformer) {
                            break;
                        }
                    }
                }
            }
            
            // Resolve based on interfaces
            if ($foundResourceTransformer === null && $foundValueTransformer === null) {
                foreach (class_implements($class) as $interface) {
                    if ($includeValueTransformer && isset($valueTransformers[$interface])) {
                        $foundValueTransformer = $valueTransformers[$interface];
                        break;
                    }
                    
                    if (isset($resourceTransformers[$interface])) {
                        $foundResourceTransformer = $resourceTransformers[$interface];
                        
                        if (! $includeValueTransformer) {
                            break;
                        }
                    }
                }
            }
            
            $transformerClass = $foundValueTransformer ?? $foundResourceTransformer ?? $transformerClass;
            $this->resolvedTransformers[$key] = $transformerClass;
        }
        
        return $transformerClass;
    }
    
    
    /**
     * Resolves the list of post processor classes for a given value
     *
     * @param   mixed  $value  The value to find the correct post processor classes for
     *
     * @return array
     */
    public function getPostProcessors($value): array
    {
        if (! is_object($value)) {
            return [];
        }
        
        $class = get_class($value);
        $key = $class . '.' . $this->getSiteIdentifier();
        if (isset($this->resolvedPostProcessors[$key])) {
            return $this->resolvedPostProcessors[$key];
        }
        
        $postProcessors = $this->getSiteConfig()['postProcessors'] ?? [];
        $list = [$postProcessors[$class] ?? []];
        
        foreach (class_parents($value) as $parent) {
            $list[] = $postProcessors[$parent] ?? [];
        }
        
        foreach (class_implements($value) as $interface) {
            $list[] = $postProcessors[$interface] ?? [];
        }
        
        return $this->resolvedPostProcessors[$key] = array_unique(array_merge(...$list));
    }
}