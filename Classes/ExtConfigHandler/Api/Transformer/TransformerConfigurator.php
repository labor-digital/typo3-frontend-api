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
 * Last modified: 2021.06.09 at 11:28
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api\Transformer;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use LaborDigital\T3fa\Core\Resource\Transformer\ResourceTransformerInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerInterface;
use LaborDigital\T3fa\ExtConfigHandler\Api\TransformerRegistrationTrait;
use Neunerlei\Configuration\State\ConfigState;

class TransformerConfigurator extends AbstractExtConfigConfigurator
{
    use TransformerRegistrationTrait;
    
    /**
     * The list of resource transformer classes by their matching target types
     *
     * @var array
     */
    protected $transformers = [];
    
    /**
     * The list of value transformer classes by their matching target types
     *
     * @var array
     */
    protected $valueTransformers = [];
    
    /**
     * A list of post processor lists by their matching target types
     *
     * @var array
     */
    protected $postProcessors = [];
    
    /**
     * A list of properties by their target class or interface name.
     *
     * Each class or interface can have allowed or denied properties.
     *
     * The denied properties have higher priority than the allowed properties.
     * Meaning a property that shows up in both arrays, will be removed from the output anyway.
     * Note: This affects the resulting array no matter which translator class you use.
     *
     * @var array
     */
    protected $properties = [];
    
    /**
     * Allows you to define which properties of a class or an interface will be included in the transformation.
     *
     * Note: This affects the resulting array no matter which translator class you use.
     *
     * @param   string      $classOrInterface   Either the class or interface name you want to configure
     * @param   array|null  $allowedProperties  Defines a list of properties that are allowed to show up in the transformed result.
     *                                          If this is a not empty array ONLY the properties in the list will show up
     * @param   array|null  $deniedProperties   Defines an optional list of properties that should NEVER show up in the transformed result.
     *                                          The denied properties have higher priority than the allowed properties.
     *                                          Meaning a property that shows up in both arrays, will be removed from the output anyway.
     *
     * @return $this
     */
    public function registerPropertyAccess(string $classOrInterface, ?array $allowedProperties, ?array $deniedProperties = null): self
    {
        $list = array_filter([
            'allowed' => array_unique($allowedProperties ?? []),
            'denied' => array_unique($deniedProperties ?? []),
        ]);
        
        if (empty($list)) {
            unset($this->properties[$classOrInterface]);
        } else {
            $this->properties[$classOrInterface] = $list;
        }
        
        return $this;
    }
    
    /**
     * Returns the registered property access rules for a given class or interface
     *
     * Note: only the access rules for the exact match are returned! Configuration of parents or interfaces of a class will not be taken into account.
     *
     * @param   string  $classOrInterface  Either the class or interface name to retrieve the current configuration for
     *
     * @return array|null Either null if there is no configuration, or an array containing "allowed" and "denied" lists
     */
    public function getPropertyAccess(string $classOrInterface): ?array
    {
        return $this->properties[$classOrInterface] ?? null;
    }
    
    /**
     * Registers a resource transformer class to convert resource object instances into arrays.
     *
     * Note: Only one transformer can be registered to handle a single class/interface at a time.
     * Class based transformers take precedence over interface based transformers, as they are more specific.
     * If multiple transformers are registered for the same type, the last defined class will be used.
     * Transformer classes registered in resource configurations will override transformers registered with this method.
     *
     * @param   string        $transformerClassName          The name of the transformer class to register.
     *                                                       The class MUST implement the ResourceTransformerInterface
     * @param   array|string  $targetClassOrInterfaceOrList  Either the name of a class, interface or
     *                                                       a list of class/interface names that should be handled
     *                                                       by the registered transformer class.
     *
     * @return $this
     * @see ResourceTransformerInterface
     */
    public function registerTransformer(string $transformerClassName, $targetClassOrInterfaceOrList): self
    {
        $this->transformers = $this->addTransformerToList(
            $this->transformers, $targetClassOrInterfaceOrList, $transformerClassName);
        
        return $this;
    }
    
    /**
     * Allows you to reset the list of all resource transformers.
     *
     * @param   array  $transformers  An array of $transformerClassName => $targetClassOrInterfaceOrList
     *
     * @return $this
     */
    public function setTransformers(array $transformers): self
    {
        $this->transformers = [];
        array_map([$this, 'registerTransformer'], array_keys($transformers), $transformers);
        
        return $this;
    }
    
    /**
     * Returns all currently configured target types and their matching resource transformer classes
     *
     * @return array
     */
    public function getTransformers(): array
    {
        return $this->transformers;
    }
    
    /**
     * Registers a new post processor for the given targets
     * Post processors run after resource transformer classes and are great to extend existing transformers
     *
     * @param   string        $postProcessorClassName        The name of the post processor class to register
     *                                                       The class has to implement the PostProcessorInterface
     * @param   string|array  $targetClassOrInterfaceOrList  Either the name of a class, interface or
     *                                                       a list of class/interface names that should be handled
     *                                                       by the registered post processor class.
     *
     * @return $this
     * @see \LaborDigital\T3fa\Core\Resource\Transformer\ResourcePostProcessorInterface
     */
    public function registerPostProcessor(string $postProcessorClassName, $targetClassOrInterfaceOrList): self
    {
        $this->postProcessors = $this->addPostProcessorToList(
            $this->postProcessors,
            $targetClassOrInterfaceOrList,
            $postProcessorClassName
        );
        
        return $this;
    }
    
    /**
     * Resets the list of all post resource processors to the given list
     *
     * @param   array  $postProcessors  The list of $postProcessorClass => $targetOrTargetList
     *
     * @return $this
     */
    public function setPostProcessors(array $postProcessors): self
    {
        $this->postProcessors = [];
        array_map([$this, 'registerPostProcessor'], array_keys($postProcessors), $postProcessors);
        
        return $this;
    }
    
    /**
     * Returns the list of all registered resource post processors.
     *
     * @return array A list of $targetClassName => $registeredPostProcessors
     */
    public function getPostProcessors(): array
    {
        return $this->postProcessors;
    }
    
    /**
     * Registers a value transformer class to convert a specific object instance into scalar values.
     *
     * Value transformers work quite similar to resource transformers but are not obligated
     * to return only array values. A good example for a value transformer is the built-in DateTransformer
     * which converts DateTime objects into javascript compatible date strings.
     *
     * Contrary to resource transformers, value transformers can't have post processors.
     *
     * @param   string        $transformerClassName          The name of the transformer class to register.
     *                                                       The class MUST implement the ValueTransformerInterface
     * @param   array|string  $targetClassOrInterfaceOrList  Either the name of a class, interface or
     *                                                       a list of class/interface names that should be handled
     *                                                       by the registered transformer class.
     *
     * @return $this
     * @see \LaborDigital\T3fa\Core\Resource\Transformer\ValueTransformerInterface
     * @see \LaborDigital\T3fa\Api\Resource\Transformer\DateTransformer
     */
    public function registerValueTransformer(string $transformerClassName, $targetClassOrInterfaceOrList): self
    {
        $this->valueTransformers = $this->addTransformerToList(
            $this->valueTransformers, $targetClassOrInterfaceOrList, $transformerClassName, TransformerInterface::class);
        
        return $this;
    }
    
    /**
     * Allows you to reset the list of all value transformers.
     *
     * @param   array  $transformers  An array of $transformerClassName => $targetClassOrInterfaceOrList
     *
     * @return $this
     */
    public function setValueTransformers(array $transformers): self
    {
        $this->valueTransformers = [];
        array_map([$this, 'registerValueTransformer'], array_keys($transformers), $transformers);
        
        return $this;
    }
    
    /**
     * Returns all currently configured target types and their matching value transformer classes
     *
     * @return array
     */
    public function getValueTransformers(): array
    {
        return $this->valueTransformers;
    }
    
    /**
     * @inheritDoc
     */
    public function finish(ConfigState $state): void
    {
        $state->set('resource', $this->transformers);
        $state->set('postProcessors', $this->postProcessors);
        $state->set('propertyAccess', $this->properties);
        $state->set('value', $this->valueTransformers);
    }
}