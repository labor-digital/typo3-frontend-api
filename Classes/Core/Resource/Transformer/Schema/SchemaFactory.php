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
 * Last modified: 2021.05.20 at 13:48
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Schema;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\Internal\PropertyAccessResolver;
use LaborDigital\T3fa\Core\Resource\Transformer\Schema\Reflection\ExtBaseReflector;
use LaborDigital\T3fa\Core\Resource\Transformer\Schema\Reflection\GenericReflector;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class SchemaFactory implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Schema\Reflection\ExtBaseReflector
     */
    protected $extBaseReflector;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Schema\Reflection\GenericReflector
     */
    protected $genericReflector;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Internal\PropertyAccessResolver
     */
    protected $accessResolver;
    
    public function __construct(
        ExtBaseReflector $extBaseReflector,
        GenericReflector $genericReflector,
        PropertyAccessResolver $accessResolver
    )
    {
        $this->extBaseReflector = $extBaseReflector;
        $this->genericReflector = $genericReflector;
        $this->accessResolver = $accessResolver;
    }
    
    public function makeSchema(object $value): TransformationSchema
    {
        $className = get_class($value);
        $accessInfo = $this->accessResolver->getAccessInfo($value);
        
        $schema = $this->makeInstance(TransformationSchema::class, [$className, $accessInfo]);
        $schema->isIterable = is_iterable($value);
        
        if ($value instanceof AbstractEntity) {
            $this->extBaseReflector->reflect($value, $schema);
        }
        
        return $schema;
    }
}