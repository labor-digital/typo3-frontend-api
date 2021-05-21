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
 * Last modified: 2021.05.20 at 16:05
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\TypoContext;


use LaborDigital\T3ba\Tool\OddsAndEnds\LazyLoadingUtil;
use LaborDigital\T3ba\Tool\TypoContext\FacetInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Transformer\ResourceTransformerInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;

class ResourceFacet implements FacetInterface
{
    use SiteConfigAwareTrait;
    
    public function __construct(TypoContext $context)
    {
        $this->context = $context;
        $this->registerConfig('t3fa.resource');
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
        $value = LazyLoadingUtil::getRealValue($value);
        
        if (is_object($value)) {
            $class = get_class($value);
        } elseif (is_string($value)) {
            $class = $value;
        } elseif (is_array($value)) {
            // If an array is given -> We probably have a collection so use the first element
            return $this->getResourceType(reset($value));
        } else {
            return null;
        }
        
        $config = $this->getSiteConfig();
        
        // $class is rather counter-intuitive here -> if a string was given
        // it has the same value that $value has, but if $value was an object, the if does not crash
        // while avoiding an additional is_string check here.
        if (isset($config['types'][$class])) {
            return $class;
        }
        
        if (isset($config['classMap'][$class])) {
            return $config['classMap'][$class];
        }
        
        if (is_iterable($value)) {
            // If an iterable is given -> We probably have a collection so use the first element
            /** @noinspection LoopWhichDoesNotLoopInspection */
            foreach ($value as $v) {
                return $this->getResourceType($v);
            }
        }
        
        return null;
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
     * Returns the transformer instance for the given value
     *
     * @param $value
     *
     * @return \LaborDigital\T3fa\Core\Resource\Transformer\ResourceTransformerInterface
     * @todo remove if not required
     */
    public function getResourceTransformer($value): ResourceTransformerInterface
    {
        return $this->context->di()
                             ->getService(TransformerFactory::class)
                             ->getTransformer($value, false);
    }
}