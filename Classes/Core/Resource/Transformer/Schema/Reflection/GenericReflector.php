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
 * Last modified: 2021.06.09 at 12:41
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Schema\Reflection;


use LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema;
use ReflectionObject;

class GenericReflector extends AbstractReflector
{
    /**
     * Receives a generic object instance and reflects a schema for the transformation for it
     *
     * @param   object                                                                    $value
     * @param   \LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema  $schema
     */
    public function reflect(object $value, TransformationSchema $schema): void
    {
        $ref = new ReflectionObject($value);
        $properties = $this->makePropertyMap($ref);
        
        $related = [];
        
        foreach ($properties as $property => [$accessType, $getter]) {
            $propRef
                = $accessType === AbstractReflector::PROPERTY_ACCESS_GETTER
                ? $ref->getMethod($getter)
                : $ref->getProperty($getter);
            $this->reflectMethodOrPropertyRelation($property, $propRef, $related);
        }
        
        $schema->properties = $properties;
        $schema->related = $related;
    }
}