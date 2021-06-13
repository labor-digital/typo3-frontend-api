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
 * Last modified: 2021.06.10 at 10:27
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformUtil;
use LaborDigital\T3fa\Core\Resource\Transformer\Implementation\DefaultResourceTransformer;
use LaborDigital\T3fa\Core\Resource\Transformer\Internal\ClassResolver;
use LaborDigital\T3fa\Core\Resource\Transformer\Internal\PropertyAccessResolver;
use LaborDigital\T3fa\Core\Resource\Transformer\Schema\SchemaRegistry;

class TransformerFactory implements PublicServiceInterface
{
    use ContainerAwareTrait;
    use T3faCacheAwareTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Schema\SchemaRegistry
     */
    protected $registry;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Internal\ClassResolver
     */
    protected $classResolver;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Internal\PropertyAccessResolver
     */
    protected $accessResolver;
    
    public function __construct(
        SchemaRegistry $registry,
        ClassResolver $classResolver,
        PropertyAccessResolver $accessResolver
    )
    {
        $this->registry = $registry;
        $this->classResolver = $classResolver;
        $this->accessResolver = $accessResolver;
    }
    
    /**
     * Returns the transformer instance which should be used to convert the given value for an array.
     *
     * @param   mixed  $value                    The value to find the correct transformer for
     * @param   bool   $includeValueTransformer  By default both resource and value transformers are returned,
     *                                           if you set this to false, only resource transformers are returned.
     *
     * @return ResourceTransformerInterface|TransformerInterface
     */
    public function getTransformer($value, bool $includeValueTransformer = true): TransformerInterface
    {
        $value = AutoTransformUtil::unifyValue($value);
        
        if ($value instanceof ResourceCollection || $value instanceof ResourceItem) {
            $value = $value->getRaw();
        }
        
        return $this->makeTransformerInstance(
            $this->classResolver->getTransformerClass($value, $includeValueTransformer),
            $this->classResolver->getPostProcessors($value),
            $this->accessResolver->getAccessInfo($value)
        );
    }
    
    /**
     * Returns true if there is a registered value transformer class for that value, false if only resource transformer exist
     *
     * @param   mixed  $value  The value to find a transformer for
     *
     * @return bool
     */
    public function hasValueTransformer($value): bool
    {
        return ! in_array(ResourceTransformerInterface::class, class_implements($this->classResolver->getTransformerClass($value, true)), true);
    }
    
    /**
     * Returns true if there is a registered transformer class for that value, false if not.
     *
     * @param   mixed  $value  The value to find a transformer for
     *
     * @return bool
     */
    public function hasNonDefaultTransformer($value): bool
    {
        return $this->classResolver->getTransformerClass($value, true) !== DefaultResourceTransformer::class;
    }
    
    /**
     * Internal factory method to create the instance of a transformer.
     * If a resource transformer is returned a fractal transformer proxy will be wrapped around the concrete transformer
     * automatically.
     *
     * @param   string      $class                 The name of the transformer class to create
     * @param   array|null  $postProcessorClasses  A list of post processor instances that should be added to created resource transformers
     * @param   array|null  $accessInfo            The property access information for resource transformers
     *
     * @return \LaborDigital\T3fa\Core\Resource\Transformer\TransformerInterface
     */
    protected function makeTransformerInstance(string $class, ?array $postProcessorClasses, ?array $accessInfo): TransformerInterface
    {
        $transformer = $this->getService($class);
        
        if ($transformer instanceof ResourceTransformerInterface) {
            $postProcessors = [];
            if (is_array($postProcessorClasses)) {
                foreach ($postProcessorClasses as $postProcessorClass) {
                    $postProcessors[] = $this->getService($postProcessorClass);
                }
            }
            
            $transformer = $this->makeInstance(
                ResourceTransformerProxy::class,
                [
                    $transformer,
                    $this->getCache(),
                    $this->t3faCacheScopeRegistry,
                    $postProcessors,
                    $accessInfo ?? [],
                ]
            );
            
        }
        
        return $transformer;
    }
}