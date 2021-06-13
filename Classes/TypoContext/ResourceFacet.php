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
 * Last modified: 2021.06.13 at 20:34
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\TypoContext;


use LaborDigital\T3ba\ExtConfig\Traits\SiteConfigAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\FacetInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformUtil;
use Neunerlei\Arrays\Arrays;

class ResourceFacet implements FacetInterface
{
    use SiteConfigAwareTrait;
    
    /**
     * Runtime cache for resolved resource types for faster lookups
     *
     * @var array
     */
    protected $resourceTypeCache = [];
    
    /**
     * Runtime cache for resolved collection types for faster lookups
     *
     * @var array
     */
    protected $collectionTypeCache = [];
    
    public function __construct(TypoContext $context)
    {
        $this->context = $context;
        $this->registerConfig('t3fa.resource', function () {
            $this->resourceTypeCache = [];
            $this->collectionTypeCache = [];
        });
    }
    
    /**
     * @inheritDoc
     */
    public static function getIdentifier(): string
    {
        return 'resource';
    }
    
    /**
     * Resolves the resource type name for the given resource type.
     *
     * @param   mixed  $value  Either the name of a class, or an object that represents the resource type to find
     *
     * @return string|null
     */
    public function getResourceType($value): ?string
    {
        return $this->getResourceTypeInternal(
            AutoTransformUtil::unifyValue($value),
            true
        );
    }
    
    /**
     * Checks if the given value is considered a "collection".
     *
     * @param   mixed  $value  The value that should be tested
     *
     * @return bool
     */
    public function isCollection($value): bool
    {
        return $this->isCollectionInternal(
            AutoTransformUtil::unifyValue($value)
        );
    }
    
    /**
     * Checks if the given value is considered a "resource".
     *
     * @param   mixed  $value  The value that should be tested
     *
     * @return bool
     */
    public function isResource($value): bool
    {
        return ! $this->isCollection($value);
    }
    
    /**
     * Returns the configuration registered for a single resource type
     *
     * @param   mixed  $value  Either the name of a class, or an object that represents the resource type to find
     *
     * @return array|null
     */
    public function getResourceConfig($value): ?array
    {
        $resourceType = $this->getResourceType($value);
        if ($resourceType === null) {
            return null;
        }
        
        return $this->getSiteConfig()['types'][$resourceType] ?? null;
    }
    
    /**
     * Internal resolver for the isCollection method. It avoids doing AutoTransformUtil::unifyValue when we are already in our
     * internal context and are sure to have done it already.
     *
     * @param   mixed  $value
     *
     * @return bool
     */
    protected function isCollectionInternal($value): bool
    {
        if (is_array($value) && ! Arrays::isAssociative($value)) {
            return true;
        }
        
        if (is_object($value)) {
            if ($value instanceof ResourceCollection) {
                return true;
            }
            
            if ($value instanceof ResourceItem) {
                return false;
            }
            
            $class = get_class($value);
        } elseif (is_string($value)) {
            $class = $value;
        } else {
            return false;
        }
        
        if (isset($this->collectionTypeCache[$class])) {
            return $this->collectionTypeCache[$class];
        }
        
        // If we have a resource type for this class (the lookup will not follow collections -> this is a resource item)
        if ($this->getResourceTypeInternal($class, false) !== null) {
            return $this->collectionTypeCache[$class] = false;
        }
        
        // If the class is a registered collection class -> this is a collection
        $collectionClasses = $this->getSiteConfig()['collectionClasses'] ?? [];
        if (in_array($class, $collectionClasses, true)) {
            return $this->collectionTypeCache[$class] = true;
        }
        
        // As a fallback check if the object is iterable -> if so = collection -> if not = resource
        return $this->collectionTypeCache[$class] = is_iterable($value);
    }
    
    /**
     * Internal resolver for the getResourceType method. It tries to resolve the resource type and return it.
     * If no matching resource type was found null will be returned. It avoids doing AutoTransformUtil::unifyValue when we are already in our
     * internal context and are sure to have done it already.
     *
     * @param   mixed  $value
     * @param   bool   $followCollections  If set to true the method will try to detect collections and retrieve the resource type of its items.
     *                                     If set to false, the method will NOT try to detect collections and simply return null if the given element is no resource itself.
     *
     * @return string|null
     */
    protected function getResourceTypeInternal($value, bool $followCollections): ?string
    {
        if (is_object($value)) {
            if ($value instanceof ResourceItem || $value instanceof ResourceCollection) {
                return $value->getResourceType();
            }
            
            $class = get_class($value);
        } elseif (is_string($value)) {
            $class = $value;
        } elseif (is_array($value) && $followCollections) {
            // If an array is given -> We probably have a collection so use the first element
            return $this->getResourceType(reset($value));
        } else {
            return null;
        }
        
        $cacheKey = $class . '.' . (int)$followCollections;
        
        if (isset($this->resourceTypeCache[$cacheKey])) {
            return $this->resourceTypeCache[$cacheKey];
        }
        
        $config = $this->getSiteConfig();
        
        // $class is rather counter-intuitive here -> if a string was given
        // it has the same value that $value has, but if $value was an object, the if does not crash
        // while avoiding an additional is_string check here.
        if (isset($config['types'][$class])) {
            return $this->resourceTypeCache[$cacheKey] = $class;
        }
        
        if (isset($config['classMap'][$class])) {
            return $this->resourceTypeCache[$cacheKey] = $config['classMap'][$class];
        }
        
        if ($followCollections) {
            if ($this->isCollectionInternal($value)) {
                /** @noinspection LoopWhichDoesNotLoopInspection */
                foreach ($value as $v) {
                    return $this->resourceTypeCache[$cacheKey] = $this->getResourceType($v);
                }
            }
        }
        
        return $this->resourceTypeCache[$cacheKey] = null;
        
    }
}