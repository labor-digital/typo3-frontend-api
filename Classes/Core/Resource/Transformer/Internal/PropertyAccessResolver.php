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
 * Last modified: 2021.06.13 at 20:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Internal;


use LaborDigital\T3ba\ExtConfig\Traits\SiteConfigAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;

class PropertyAccessResolver
{
    use SiteConfigAwareTrait;
    
    /**
     * Cache to avoid repetitive info resolving when multiple values of the same type are transformed
     *
     * @var array
     */
    protected $resolvedAccessInfo = [];
    
    public function __construct(TypoContext $context)
    {
        $this->context = $context;
        $this->registerConfig('t3fa.transformer.propertyAccess');
    }
    
    /**
     * Resolves the list of allowed and denied properties that were configured for the given value
     *
     * @param   string|object|mixed  $value  Either an object or a class name to resolve the access info for
     *
     * @return array
     */
    public function getAccessInfo($value): array
    {
        if (is_object($value)) {
            $class = get_class($value);
        } elseif (is_string($value) && class_exists($value)) {
            $class = $value;
        } else {
            return ['allowed' => [], 'denied' => []];
        }
        
        $key = $class . '.' . $this->getSiteIdentifier();
        
        if (isset($this->resolvedAccessInfo[$key])) {
            return $this->resolvedAccessInfo[$key];
        }
        
        $allowed = [];
        $denied = [];
        
        $config = $this->getSiteConfig();
        
        $classes = array_merge(
            [$class],
            class_parents($class),
            class_implements($class)
        );
        
        foreach ($classes as $lookupClass) {
            if (isset($config[$lookupClass])) {
                $allowed[] = $config[$lookupClass]['allowed'] ?? [];
                $denied[] = $config[$lookupClass]['denied'] ?? [];
            }
        }
        
        // The id field MUST be always allowed
        $allowed = array_unique(array_merge(...$allowed));
        if (! empty($allowed) && ! in_array('id', $allowed, true)) {
            $allowed[] = 'id';
        }
        
        // The id field CAN'T be denied as it is integral part of the resource api
        $denied = array_unique(array_merge(...$denied));
        if (in_array('id', $denied, true)) {
            unset($denied[array_search('id', $denied, true)]);
        }
        
        return $this->resolvedAccessInfo[$key] = [
            'allowed' => $allowed,
            'denied' => $denied,
        ];
    }
}