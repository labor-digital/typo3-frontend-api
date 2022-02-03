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
 * Last modified: 2021.06.09 at 13:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Schema\Reflection;


use LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema;
use ReflectionClass;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

class ExtBaseReflector extends AbstractReflector
{
    
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;
    
    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;
    
    public function __construct(DataMapper $dataMapper, ReflectionService $reflectionService)
    {
        $this->dataMapper = $dataMapper;
        $this->reflectionService = $reflectionService;
    }
    
    /**
     * Receives the instance of an abstract entity and reflects a schema for the transformation for it
     *
     * @param   \TYPO3\CMS\Extbase\DomainObject\AbstractEntity                            $value
     * @param   \LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema  $schema
     */
    public function reflect(AbstractEntity $value, TransformationSchema $schema): void
    {
        $className = get_class($value);
        $ref = new ReflectionClass($className);
        $extBaseRef = $this->reflectionService->getClassSchema($className);
        $dataMap = $this->dataMapper->getDataMap($className);
        $properties = $this->makePropertyMap($ref);
        
        $related = [];
        
        foreach ($properties as $property => [$accessType, $getter]) {
            $columnMap = $dataMap->getColumnMap($property);
            if ($columnMap === null || $columnMap->getTypeOfRelation() === ColumnMap::RELATION_NONE) {
                $propRef
                    = $accessType === AbstractReflector::PROPERTY_ACCESS_GETTER
                    ? $ref->getMethod($getter)
                    : $ref->getProperty($getter);
                
                $this->reflectMethodOrPropertyRelation($property, $propRef, $related);
                continue;
            }
            
            // @todo this has to be fixed for PHP8 because multiple return types can be the case
            $prop = $extBaseRef->getProperty($property);
            $propertyClass = $prop->getElementType() ?? $prop->getType();
            
            if (empty($propertyClass) || in_array($propertyClass,
                    ['integer', 'int', 'boolean', 'float', 'double', 'string', 'array', 'object', 'null'], true)) {
                continue;
            }
            
            if ($this->configRepository->isComplexValue($propertyClass)) {
                continue;
            }
            
            $related[$property] = $columnMap->getTypeOfRelation() !== ColumnMap::RELATION_HAS_ONE;
        }
        
        $schema->properties = $properties;
        $schema->related = $related;
    }
}