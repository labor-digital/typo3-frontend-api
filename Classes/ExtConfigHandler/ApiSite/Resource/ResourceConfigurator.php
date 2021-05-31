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
 * Last modified: 2021.05.31 at 13:31
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource;


use LaborDigital\T3fa\Core\Resource\Query\Parser;
use LaborDigital\T3fa\Core\Resource\Route\DefaultResourceController;
use LaborDigital\T3fa\Core\Resource\Route\ResourceControllerInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\ResourceTransformerInterface;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\ApiSiteConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Routing\RoutingConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Transformer\TransformerConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\TransformerRegistrationTrait;
use Neunerlei\Options\Options;

class ResourceConfigurator
{
    use TransformerRegistrationTrait;
    
    // @todo implement Cache options trait
    
    /**
     * The unique name of this resource type
     *
     * @var string
     */
    protected $resourceType;
    
    /**
     * The class which is responsible for handling the resource currently configured
     *
     * @var string
     */
    protected $resourceClass;
    
    /**
     * The api controller class that is used to handle the route requests for this resource
     *
     * @var string
     */
    protected $controllerClass = DefaultResourceController::class;
    
    /**
     * Optional options that may have been provided when the resource was registered
     *
     * @var array|null
     */
    protected $options;
    
    /**
     * The list of transformer classes with their registration args
     *
     * @var array
     */
    protected $transformers = [];
    
    /**
     * A list of post processor classes with their registration args
     *
     * @var array
     */
    protected $postProcessors = [];
    
    /**
     * A list of classes / entities / extBase models that are handled by this resource.
     * Those classes can be used as equivalents of of the resource typeThose classes can be used as equivalents of of the resource type
     *
     * @var array
     */
    protected $classes = [];
    
    /**
     * The number of items that should, by default be displayed on a single page when the "collection" route is called.
     * This can be overwritten using the page.size argument.
     * NULL means the default query
     *
     * @var int|null
     */
    protected $pageSize;
    
    /**
     * A query representing the query parameters of a JSON:API query. The given values will be merged into every
     * collection request of this resource. The actual request can override the defaults.
     *
     * Note: if $pageSize is set, it will always overrule the page.size argument in this query array
     *
     * @var array
     */
    protected $defaultQuery = [];
    
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
    
    public function __construct(string $resourceType, string $resourceClass, ?array $options)
    {
        $this->resourceType = $resourceType;
        $this->resourceClass = $resourceClass;
        $this->options = $options;
    }
    
    /**
     * Returns the name of the resource type you are currently configuring
     *
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
    
    /**
     * Returns the options that have been given when the resource was registered
     *
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }
    
    /**
     * Allows you to validate the resource options that have been passed when the resource was registered
     * The validation will also take place if no options have been given! So make sure you set the defaults
     *
     * @param   array  $definition  A valid definition for Options::make() to apply to the options
     *
     * @see \Neunerlei\Options\Options::make()
     */
    public function validateOptions(array $definition): void
    {
        $this->options = Options::make($this->options ?? [], $definition);
    }
    
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
        $this->properties[$classOrInterface] = [
            'allowed' => array_unique($allowedProperties ?? []),
            'denied' => array_unique($deniedProperties ?? []),
        ];
        
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
     * Returns the list of classes / entities / extBase models that are handled by this resource.
     *
     * @return array
     * @see setClasses()
     */
    public function getClasses(): array
    {
        return $this->classes;
    }
    
    /**
     * Sets a list of classes / entities / extBase models that are handled by this resource.
     * Those classes can be used as equivalents of of the resource type
     *
     * @param   array  $classes
     *
     * @return $this
     */
    public function setClasses(array $classes): self
    {
        $this->classes = array_unique($classes);
        
        return $this;
    }
    
    /**
     * Adds a single class / entity / extBase model that will be handled by this resource
     *
     * @param   string  $class
     *
     * @return $this
     * @see setClasses()
     */
    public function registerClass(string $class): self
    {
        return $this->setClasses(
            array_merge($this->classes, [$class])
        );
    }
    
    /**
     * Returns the default number of items on a single page when the collection of this resource is requested
     *
     * @return int|null Either the number of items, or null to use the default page size
     */
    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }
    
    /**
     * Can be used to define the default number of items that are shown on a single page
     * when the collection of this resource is requested
     *
     * @param   int|null  $pageSize  Either the number of items, or null to use the default page size
     *
     * @return $this
     */
    public function setPageSize(?int $pageSize): self
    {
        $this->pageSize = $pageSize === null ? null : max(1, $pageSize);
        
        return $this;
    }
    
    /**
     * Returns the configured default query in a valid JSON:API format
     *
     * @return array
     */
    public function getDefaultQuery(): array
    {
        return $this->defaultQuery;
    }
    
    /**
     * Sets an array representing the query parameters of a JSON:API query. The given values will be merged into every
     * collection request of this resource. The actual request can override the defaults.
     *
     * @param   array  $defaultQuery
     */
    public function setDefaultQuery(array $defaultQuery): void
    {
        $this->defaultQuery = $defaultQuery;
    }
    
    /**
     * Registers a transformer class to convert resource object instances into arrays.
     *
     * Note: Only one transformer can be registered to handle a single class/interface at a time.
     * Class based transformers take precedence over interface based transformers, as they are more specific.
     * If multiple transformers are registered for the same type, the last defined class will be used.
     * Transformer classes registered in resource configurations will override transformers registered with this method.
     *
     * @param   string             $transformerClassName          The name of the transformer class to register.
     *                                                            The class MUST implement the ResourceTransformerInterface
     * @param   array|string|null  $targetClassOrInterfaceOrList  Either the name of a class, interface or
     *                                                            a list of class/interface names that should be handled
     *                                                            by the registered transformer class.
     *                                                            IF OMITTED (NULL): The transformer will be registered for ALL classes
     *                                                            that have been set using registerClass()
     *
     * @return $this
     * @see ResourceTransformerInterface
     */
    public function registerTransformer(string $transformerClassName, $targetClassOrInterfaceOrList = null): self
    {
        $this->transformers[] = [$transformerClassName, $targetClassOrInterfaceOrList];
        
        return $this;
    }
    
    /**
     * Allows you to reset the list of all transformers.
     *
     * @param   array  $transformers  An array of $transformerClassName => $targetClassOrInterfaceOrList
     *                                If $targetClassOrInterfaceOrList is NULL: The transformer will be registered for ALL classes
     *                                that have been set using registerClass()
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
     * Returns all LOCALLY configured target types and their matching transformer classes
     *
     * @return array
     */
    public function getTransformers(): array
    {
        $list = [];
        foreach ($this->transformers as $args) {
            $this->addTransformerToList($list, $args[1] ?? $this->classes, $args[0]);
        }
        
        return $list;
    }
    
    /**
     * Registers a new post processor for the given targets
     * Post processors run after transformer classes and are great to extend existing transformers
     *
     * @param   string             $postProcessorClassName        The name of the post processor class to register
     *                                                            The class has to implement the PostProcessorInterface
     * @param   string|array|null  $targetClassOrInterfaceOrList  Either the name of a class, interface or
     *                                                            a list of class/interface names that should be handled
     *                                                            by the registered post processor class.
     *                                                            IF OMITTED (NULL): The post processor will be registered for ALL classes
     *                                                            that have been set using registerClass()
     *
     * @return $this
     * @see \LaborDigital\T3fa\Core\Resource\Transformer\ResourcePostProcessorInterface
     */
    public function registerPostProcessor(string $postProcessorClassName, $targetClassOrInterfaceOrList = null): self
    {
        $this->postProcessors[] = [$postProcessorClassName, $targetClassOrInterfaceOrList];
        
        return $this;
    }
    
    /**
     * Resets the list of all post processors to the given list
     *
     * @param   array  $postProcessors  The list of $postProcessorClass => $targetOrTargetList
     *                                  If $targetOrTargetList is NULL: The post processor will be registered for ALL classes
     *                                  that have been set using registerClass()
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
     * Returns the list of all LOCALLY registered post processors.
     *
     * @return array A list of $targetClassName => $registeredPostProcessors
     */
    public function getPostProcessors(): array
    {
        $list = [];
        foreach ($this->postProcessors as $args) {
            $this->addPostProcessorToList($list, $args[1] ?? $this->classes, $args[0]);
        }
        
        return $list;
    }
    
    /**
     * Returns the currently configured controller class to handle this resources api requests
     *
     * @return string
     */
    public function getControllerClass(): string
    {
        return $this->controllerClass;
    }
    
    /**
     * Allows you to override the api controller class that is used to resolve this resource
     *
     * @param   string  $controllerClass  The class must implement the ResourceControllerInterface
     *
     * @see \LaborDigital\T3fa\Core\Resource\Route\ResourceControllerInterface
     * @see DefaultResourceController
     */
    public function setControllerClass(string $controllerClass): self
    {
        if (! class_exists($controllerClass)) {
            throw new \InvalidArgumentException('Invalid resource controller class: "' . $controllerClass . '" the class does not exist');
        }
        
        if (! in_array(ResourceControllerInterface::class, class_implements($controllerClass), true)) {
            throw new \InvalidArgumentException('Invalid resource controller class: "' . $controllerClass . '" the class has to implement the required interface: "' . ResourceControllerInterface::class . '"');
        }
        
        $this->controllerClass = $controllerClass;
        
        return $this;
    }
    
    /**
     * Provides the collected configuration to the provided storage lists
     *
     * @param   TransformerConfigurator  $transformerCollector
     * @param   array                    $types
     * @param   array                    $classMap
     */
    public function finish(ApiSiteConfigurator $configurator, array &$types, array &$classMap): void
    {
        $query = Parser::parse($this->defaultQuery);
        if ($this->pageSize !== null) {
            $query['page']['size'] = $this->pageSize;
        }
        if (empty($query['meta']['additional'])) {
            unset($query['meta']['additional']);
        }
        $query = array_filter($query);
        
        $types[$this->resourceType] = [
            'type' => $this->resourceType,
            'pageSize' => $this->pageSize,
            'class' => $this->resourceClass,
            'options' => $this->options ?? [],
            'defaultQuery' => $query,
        ];
        
        $classMap = array_merge(
            $classMap,
            [$this->resourceClass => $this->resourceType],
            array_fill_keys($this->classes, $this->resourceType)
        );
        
        $this->finishTransformerConfig($configurator->transformer());
        $this->finishRoutes($configurator->routing());
    }
    
    /**
     * Injects the locally configured transformer data into the transformer configuration object
     *
     * @param   \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Transformer\TransformerConfigurator  $configurator
     */
    protected function finishTransformerConfig(TransformerConfigurator $configurator): void
    {
        foreach ($this->transformers as $args) {
            $configurator->registerTransformer($args[0], $args[1] ?? $this->classes);
        }
        foreach ($this->postProcessors as $args) {
            $configurator->registerPostProcessor($args[0], $args[1] ?? $this->classes);
        }
        foreach ($this->properties as $target => $args) {
            $configurator->registerPropertyAccess($target, ...array_values($args));
        }
    }
    
    /**
     * Injects the required routes into the route configurator
     *
     * @param   \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Routing\RoutingConfigurator  $configurator
     */
    protected function finishRoutes(RoutingConfigurator $configurator): void
    {
        $group = $configurator->routes('/resources/' . $this->getResourceType());
        
        // Single
        $group->get('/{id}', [$this->controllerClass, 'singleAction'])
            // @todo pass cache options along
              ->setName('resource-' . $this->getResourceType() . '-single')
              ->setAttribute('resourceType', $this->getResourceType());
        
        // Single related
        $group->get('/{id}/{related}', [$this->controllerClass, 'relationAction'])
            // @todo pass cache options along
              ->setName('resource-' . $this->getResourceType() . '-relation')
              ->setAttribute('resourceType', $this->getResourceType());
        
        // Relationships
        $group->get('/{id}/relationships/{relationship}', [$this->controllerClass, 'relationshipAction'])
            // @todo pass cache options along
              ->setName('resource-' . $this->getResourceType() . '-relationships')
              ->setAttribute('resourceType', $this->getResourceType());
        
        // Collection
        $group->get('/', [$this->controllerClass, 'collectionAction'])
            // @todo pass cache options along
              ->setName('resource-' . $this->getResourceType() . '-collection')
              ->setAttribute('resourceType', $this->getResourceType());
        
    }
}