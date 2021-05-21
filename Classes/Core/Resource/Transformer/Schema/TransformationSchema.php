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
 * Last modified: 2021.05.20 at 14:33
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Schema;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerScope;

class TransformationSchema implements NoDiInterface
{
    /**
     * The name of the class that is represented by this schema
     *
     * @var string
     */
    public $className;
    
    /**
     * The list of "allowed" and "denied" properties in this schema
     *
     * @var array
     */
    public $accessInfo;
    
    /**
     * True if the object this schema applies to is iterable
     * Therefore it probably should be handled like a collection
     *
     * @var bool
     */
    public $isIterable = false;
    
    /**
     * The name of the property that contains the unique "id" attribute for the class
     *
     * @var string
     */
    public $idProperty;
    
    /**
     * A list of property names and their matching getter methods
     * Properties contain both "attributes" and "related" elements
     *
     * @var array
     */
    public $properties = [];
    
    /**
     * The list of property names that contain related/includable resources
     * The name of the property is the key and a boolean as value: TRUE -> Collection | FALSE -> Single resource
     *
     * @var array
     */
    public $related = [];
    
    public function __construct(string $className, array $accessInfo)
    {
        $this->className = $className;
        $this->accessInfo = $accessInfo;
    }
    
    /**
     * Extracts the id value from the given object
     * If no id property can be determined, a random id will be returned
     *
     * @param   object  $value  The object to extract the id value from
     *
     * @return string|void
     */
    public function getId(object $value)
    {
        foreach ([$this->idProperty, 'id', 'uid'] as $idProperty) {
            if (isset($this->properties[$idProperty])) {
                return $this->getValue($value, $idProperty);
            }
        }
        
        return md5((string)microtime(true));
    }
    
    /**
     * Calls the correct getter to retrieve the value for a certain property
     *
     * @param   object  $value     The object to extract the value from
     * @param   string  $property  The name of the property to extract from the
     *
     * @return null|mixed
     */
    public function getValue(object $value, string $property)
    {
        if (! isset($this->properties[$property])) {
            return null;
        }
        
        $method = $this->properties[$property];
        
        return $value->$method();
    }
    
    /**
     * Retrieves all "attributes" of the value, which are all properties without "id" and anything that can be "included" / which is related.
     *
     * @param   object  $value  The object to extract the attributes from
     *
     * @return array
     */
    public function getAttributes(object $value): array
    {
        $attributes = [];
        
        foreach ($this->getAllowedProperties() as $property) {
            if (isset($this->related[$property]) || $property === 'id') {
                continue;
            }
            
            $attributes[$property] = $this->getValue($value, $property);
        }
        
        return $attributes;
    }
    
    /**
     * Returns the list of all properties that are allowed by the configuration.
     *
     * @return array
     */
    public function getAllowedProperties(): array
    {
        $properties = array_keys($this->properties);
        
        if (TransformerScope::$accessCheck) {
            if (! empty($this->accessInfo['allowed'])) {
                $properties = array_intersect($properties, $this->accessInfo['allowed']);
            }
            
            if (! empty($this->accessInfo['denied'])) {
                $properties = array_diff($properties, $this->accessInfo['denied']);
            }
        }
        
        return $properties;
    }
}