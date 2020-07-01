<?php
declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.08.08 at 10:43
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3BetterApi\LazyLoading\LazyLoadingTrait;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use ReflectionMethod;
use Traversable;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

class TransformerConfigGenerator implements SingletonInterface
{
    use LazyLoadingTrait;
    
    public const EMPTY_VALUE_MARKER = "--**TRANSFORMER_EMPTY_VALUE**--";
    
    protected const INTERNAL_PROPERTIES = ["pid", "lazyLoading"];
    
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;
    
    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;
    
    /**
     * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
     */
    protected $container;
    
    /**
     * The already created configurations as first level cache
     *
     * @var array
     */
    protected $configurations = [];
    
    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;
    
    /**
     * TransformerConfigGenerator constructor.
     *
     * @param   \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper              $dataMapper
     * @param   \TYPO3\CMS\Extbase\Reflection\ReflectionService                       $reflectionService
     * @param   \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface         $container
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(
        DataMapper $dataMapper,
        ReflectionService $reflectionService,
        TypoContainerInterface $container,
        FrontendApiConfigRepository $configRepository
    ) {
        $this->dataMapper        = $dataMapper;
        $this->reflectionService = $reflectionService;
        $this->container         = $container;
        $this->configRepository  = $configRepository;
    }
    
    /**
     * Generates the transformer configuration object based on the given value.
     * The configuration is calculated based on class / value type and returned as a unified object.
     * The configuration is cached for the current execution but not persisted
     *
     * @param   mixed                $value
     * @param   string               $resourceType
     * @param   ResourceConfig|null  $resourceConfig
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
     */
    public function makeTransformerConfigFor(
        $value,
        string $resourceType,
        ?ResourceConfig $resourceConfig
    ): TransformerConfig {
        // Read value metadata
        $isObjectTransformation = is_object($value);
        $type                   = $isObjectTransformation ? get_class($value) : gettype($value);
        
        // Check for cached value
        if ($isObjectTransformation && isset($this->configurations[$type])) {
            return $this->configurations[$type];
        }
        
        // Make default config
        $config                   = $this->container->get(TransformerConfig::class);
        $config->resourceConfig   = $resourceConfig;
        $config->resourceType     = $resourceType;
        $config->transformerClass = Transformer::class;
        
        // Check if we have to transform an object
        if ($isObjectTransformation) {
            // Handle self transforming objects
            if (in_array(SelfTransformingInterface::class, class_implements($type), true)) {
                $config->isSelfTransforming = true;
            }
            
            // Handle special object transformation
            if (($specialObjectTransformer = $this
                    ->configRepository
                    ->resource()
                    ->getSpecialObjectTransformerFor($type)) !== null) {
                $config->specialObjectTransformerClass = $specialObjectTransformer;
            } // Create config for extbase entity
            elseif (in_array(AbstractEntity::class, class_parents($type), true)) {
                $this->makeConfigForEntity($config, $type);
            } // Handle iterables
            elseif (is_iterable($value)) {
                $config->isArray = true;
            } // Handle generic objects
            else {
                $this->makeConfigForGenericObject($config, $value, $type);
            }
        } // Scalar values
        elseif (is_scalar($value)) {
            $config->isScalar = true;
        } // Array values
        elseif (is_array($value)) {
            $config->isArray = true;
        } // Not found
        else {
            $config->isNull = true;
        }
        
        // Read class name and post processors from resource config
        if ($resourceConfig !== null) {
            // Select transformer class
            if (! empty($resourceConfig->transformerClass)) {
                $config->transformerClass = $resourceConfig->transformerClass;
            }
            
            // Create post processor list
            if (! empty($resourceConfig->transformerPostProcessors)) {
                foreach (array_keys($resourceConfig->transformerPostProcessors) as $processor) {
                    $config->postProcessors[] = function (array $result, $value) use (
                        $processor,
                        $resourceType,
                        $resourceConfig
                    ): array {
                        /** @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\PostProcessing\ResourcePostProcessorInterface $i */
                        $i = $this->container->get($processor);
                        
                        return $i->process($result, $value, $resourceType, $resourceConfig);
                    };
                }
            }
            
            // Apply include and deleted property configuration
            $hasAllowed = ! empty($resourceConfig->allowedProperties);
            $hasDenied  = ! empty($resourceConfig->deniedProperties);
            if ($hasAllowed || $hasDenied) {
                foreach (["includes", "attributes"] as $list) {
                    $filtered = [];
                    foreach ($config->$list as $k => $v) {
                        // Check if the property is allowed
                        if ($hasAllowed && ! in_array($k, $resourceConfig->allowedProperties, true)) {
                            continue;
                        }
                        
                        // Check if the property is denied
                        if ($hasDenied && in_array($k, $resourceConfig->deniedProperties, true)) {
                            continue;
                        }
                        
                        // Add to filtered list
                        $filtered[$k] = $v;
                    }
                    $config->$list = $filtered;
                }
            }
        }
        
        // Done
        return $this->configurations[$type] = $config;
    }
    
    /**
     * Generates the config for an extbase entity class
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig  $config
     * @param   string                                                                   $className
     */
    protected function makeConfigForEntity(TransformerConfig $config, string $className): void
    {
        // Prepare data-map and reflection
        $dataMap   = $this->dataMapper->getDataMap($className);
        $ref       = $this->reflectionService->getClassSchema($className);
        $getterMap = $this->makeGetterMap($className);
        
        // Find the include properties
        foreach ($ref->getProperties() as $property => $propertyRef) {
            // Ignore internal properties
            if (! empty($config->resourceConfig)
                && $config->resourceConfig->includeInternalProperties !== true
                && in_array($property, static::INTERNAL_PROPERTIES, true)) {
                continue;
            }
            
            // Check if we have a getter
            if (! isset($getterMap[$property])) {
                continue;
            }
            
            // Check if this has a relation
            $columnMap = $dataMap->getColumnMap($property);
            if ($columnMap === null) {
                continue;
            }
            if ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_NONE) {
                continue;
            }
            
            // Check if this property can be included
            $getter    = $getterMap[$property];
            $prop      = $ref->getProperty($property);
            $propClass = empty($prop["elementType"]) ? $prop["type"] : $prop["elementType"];
            
            // Ignore simple value types
            $isSimpleValue = in_array($propClass,
                ['integer', 'int', 'boolean', 'float', 'double', 'string', 'array', 'object', 'null'], true);
            if ($isSimpleValue) {
                continue;
            }
            
            // Make the resource type
            $resourceType = $this->configRepository
                ->resource()
                ->getResourceTypeByClassName($propClass, true);
            
            // Build the include definition
            $config->includes[$property] = [
                "isCollection" => $columnMap->getTypeOfRelation() !== ColumnMap::RELATION_HAS_ONE,
                "class"        => $propClass,
                "resourceType" => $resourceType,
                "getter"       => static function (AbstractEntity $entity) use ($getter) {
                    return $entity->$getter();
                },
            ];
        }
        
        // Check if there are additional getters that look interesting
        foreach (
            array_diff_key($getterMap, Arrays::attach($config->attributes, $config->includes)) as $property => $getter
        ) {
            // Ignore internal properties
            if (! empty($config->resourceConfig)
                && $config->resourceConfig->includeInternalProperties !== true
                && in_array($property, static::INTERNAL_PROPERTIES, true)) {
                continue;
            }
            
            // Ignore the uid
            if ($property === "uid") {
                continue;
            }
            
            // Use reflection to get information about the class
            $ref           = new ReflectionMethod($className, $getter);
            $getterClosure = static function (AbstractEntity $entity) use ($getter) {
                return $entity->$getter();
            };
            
            // Ignore if there is no return type
            if (! $ref->hasReturnType()) {
                $config->attributes[$property] = $getterClosure;
                continue;
            }
            
            // Make classes includable, if non of the simple classes
            /** @noinspection NullPointerExceptionInspection */
            $returnType                  = $ref->getReturnType()->getName();
            $hasSpecialObjectTransformer = $this->configRepository
                                               ->resource()
                                               ->getSpecialObjectTransformerFor($returnType)
                                           !== null;
            if (! $hasSpecialObjectTransformer && class_exists($returnType)) {
                $resourceType                = $this->configRepository
                    ->resource()
                    ->getResourceTypeByClassName($returnType, true);
                $config->includes[$property] = [
                    "isCollection" => in_array(Traversable::class, class_implements($returnType), true),
                    "class"        => $returnType,
                    "resourceType" => $resourceType,
                    "getter"       => $getterClosure,
                ];
            } else {
                $config->attributes[$property] = $getterClosure;
            }
        }
        
        // Set the id getter
        $config->idGetter = static function (AbstractEntity $entity) {
            return $entity->getUid();
        };
    }
    
    /**
     * Generates the configuration for a generic object
     *
     * @param   TransformerConfig  $config
     * @param   object             $value
     * @param   string             $className
     */
    protected function makeConfigForGenericObject(TransformerConfig $config, ?object $value, string $className): void
    {
        // Prepare list of readable elements
        $getterMap            = $this->makeGetterMap($className);
        $properties           = is_null($value) ? get_class_vars($className) : get_object_vars($value);
        $combinedPropertyKeys = array_unique(Arrays::attach(array_keys($getterMap), array_keys($properties)));
        
        // Build attributes
        foreach ($combinedPropertyKeys as $property) {
            // Make property getter
            $getter                        = $getterMap[$property] ?? null;
            $getterClosure                 = static function (object $obj) use ($getter, $property) {
                if (empty($getter)) {
                    return $obj->$property;
                }
                
                return $obj->$getter();
            };
            $config->attributes[$property] = $getterClosure;
        }
        
        // Set the id getter
        if (isset($config->attributes["id"])) {
            $config->idGetter = $config->attributes["id"];
        } elseif (isset($config->attributes["uid"])) {
            $config->idGetter = $config->attributes["uid"];
        } else {
            $config->idGetter = static function () {
                return md5(microtime(true));
            };
        }
    }
    
    /**
     * Generates a list of getter methods and their property names based on the given class name
     *
     * @param   string  $className
     *
     * @return array
     */
    protected function makeGetterMap(string $className): array
    {
        $getterMap = [];
        foreach (get_class_methods($className) as $getter) {
            $getterLc = strtolower($getter);
            if (strpos($getterLc, "is") === 0 || strpos($getterLc, "has") === 0
                || strpos($getterLc, "get") === 0) {
                $ref = new ReflectionMethod($className, $getter);
                if ($ref->getNumberOfRequiredParameters() !== 0) {
                    continue;
                }
                $getterMap[Inflector::toProperty($getter)] = $getter;
            }
        }
        
        return $getterMap;
    }
}
