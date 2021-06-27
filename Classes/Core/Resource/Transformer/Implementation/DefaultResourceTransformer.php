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
 * Last modified: 2021.06.22 at 13:11
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Implementation;


use LaborDigital\T3fa\Core\Resource\Transformer\AbstractResourceTransformer;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoMagicTransformerTrait;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformUtil;
use LaborDigital\T3fa\Core\Resource\Transformer\Schema\SchemaAwareTransformerTrait;

class DefaultResourceTransformer extends AbstractResourceTransformer
{
    use SchemaAwareTransformerTrait;
    use AutoMagicTransformerTrait;
    
    /**
     * @inheritDoc
     */
    public function transform($value): array
    {
        $this->availableIncludes = [];
        
        $value = AutoTransformUtil::unifyValue($value);
        
        if (is_array($value)) {
            return $this->autoTransform($value);
        }
        
        if (is_scalar($value)) {
            return [
                'id' => md5((string)microtime(true)),
                'value' => $value,
            ];
        }
        
        if (is_object($value)) {
            return $this->handleObjectTransform($value);
        }
        
        return ['id' => null];
    }
    
    /**
     * Converts the provided $value based on the transformation schema
     *
     * @param   object  $value  The value to transform into an array
     *
     * @return array
     */
    protected function handleObjectTransform(object $value): array
    {
        $schema = $this->getSchema($value);
        $this->availableIncludes = array_keys($schema->related);
        
        return array_merge(
            ['id' => $schema->getId($value)],
            $this->autoTransform($schema->getAttributes($value))
        );
    }
    
    /**
     * Magic method to automatically handle the include method calls
     *
     * @param $name
     * @param $arguments
     *
     * @return \League\Fractal\Resource\ResourceAbstract|null
     */
    public function __call($name, $arguments)
    {
        if (! str_starts_with($name, 'include')) {
            return null;
        }
        
        $value = $arguments[0];
        $property = lcfirst(substr($name, 7));
        
        if (! is_object($value)) {
            return $this->null();
        }
        
        $returnValue = $this->getSchema($value)->getValue($value, $property);
        
        if (! is_object($returnValue)) {
            return $this->null();
        }
        
        if ($this->getSchema($returnValue)->isCollection) {
            return $this->autoIncludeCollection($returnValue);
        }
        
        return $this->autoIncludeItem($returnValue);
    }
}