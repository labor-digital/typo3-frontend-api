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
 * Last modified: 2021.05.20 at 14:13
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Schema;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Cache\CacheConsumerInterface;
use LaborDigital\T3ba\Tool\Cache\CacheInterface;

class SchemaRegistry implements CacheConsumerInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Cache\CacheInterface
     */
    protected $systemCache;
    
    /**
     * The list of loaded schemas
     *
     * @var array
     */
    protected $loadedSchemas = [];
    
    public function __construct(CacheInterface $systemCache)
    {
        $this->systemCache = $systemCache;
    }
    
    /**
     * Returns the transformation schema for the given object
     * The result of this method will be cached, both locally and persisted in the system cache
     *
     * @param   object  $value  The object to find the transformation schema for
     *
     * @return \LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema
     */
    public function getSchema(object $value): TransformationSchema
    {
        $className = get_class($value);
        
        if (isset($this->loadedSchemas[$className])) {
            return $this->loadedSchemas[$className];
        }
        
        return $this->loadedSchemas[$className]
            = $this->systemCache->remember(function () use ($value) {
            return $this->getService(SchemaFactory::class)->makeSchema($value);
            
        }, ['transformationSchema', $className]);
        
    }
}