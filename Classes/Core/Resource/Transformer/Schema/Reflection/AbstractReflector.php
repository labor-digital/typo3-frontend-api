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
 * Last modified: 2021.06.09 at 12:28
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Schema\Reflection;


use LaborDigital\T3ba\Tool\OddsAndEnds\ReflectionUtil;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use Neunerlei\Inflection\Inflector;
use ReflectionMethod;
use ReflectionProperty;

abstract class AbstractReflector
{
    use TypoContextAwareTrait;
    
    public const PROPERTY_ACCESS_DIRECT = 'directAccess';
    public const PROPERTY_ACCESS_GETTER = 'getterAccess';
    
    /**
     * Generates a list of getter methods and their property names based on the given reflection
     *
     * @param   \ReflectionClass  $ref
     *
     * @return array
     */
    protected function makePropertyMap(\ReflectionClass $ref): array
    {
        $properties = [];
        
        // Inflect the list of public properties
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            
            $propertyName = $property->getName();
            
            if (isset($properties[$propertyName])) {
                continue;
            }
            
            $properties[$propertyName] = [static::PROPERTY_ACCESS_DIRECT, $propertyName];
        }
        
        // Getters will have priority over the direct access on public properties
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }
            
            $methodName = $method->getName();
            $methodNameLc = strtolower($methodName);
            
            if (str_starts_with($methodNameLc, 'is')
                || str_starts_with($methodNameLc, 'has')
                || str_starts_with($methodNameLc, 'get')) {
                if ($method->getNumberOfRequiredParameters() !== 0) {
                    continue;
                }
                
                $properties[Inflector::toProperty($methodName)] = [static::PROPERTY_ACCESS_GETTER, $methodName];
            }
        }
        
        // If there is a "uid" but no "id" we use "uid" as "id", otherwise simple remove "uid"
        if (isset($properties['uid'])) {
            if (! isset($properties['id'])) {
                $properties['id'] = $properties['uid'];
            }
            
            unset($properties['uid']);
        }
        
        // The Pid is never part of a property map
        unset($properties['pid']);
        
        return $properties;
    }
    
    /**
     * Checks if the return type of the given property/getter is a resource and therefore can be included
     * If so, the property will automatically be added to the list of $related
     *
     * @param   string             $propertyName  The name of the property to check
     * @param   \ReflectionMethod  $ref           The reflection of the property's getter method
     * @param   array              $related       The list of related elements
     */
    protected function reflectMethodOrPropertyRelation(string $propertyName, $ref, array &$related): void
    {
        if (! $ref instanceof ReflectionProperty && ! $ref instanceof ReflectionMethod) {
            return;
        }
        
        $types = ReflectionUtil::parseType($ref);
        
        if (count($types) !== 1) {
            return;
        }
        
        $type = reset($types);
        
        if ($this->getTypoContext()->resource()->getResourceType($type) !== null) {
            $related[$propertyName] = false;
        }
    }
}