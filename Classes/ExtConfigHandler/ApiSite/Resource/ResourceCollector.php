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
 * Last modified: 2021.05.19 at 23:31
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\ExtConfig\Interfaces\ElementKeyProviderInterface;
use LaborDigital\T3fa\Core\Resource\ResourceInterface;
use Neunerlei\Inflection\Inflector;
use Neunerlei\PathUtil\Path;

class ResourceCollector implements NoDiInterface
{
    /**
     * The identifier for the currently configured site
     *
     * @var string
     */
    protected $siteKey;
    
    /**
     * The list of registered resource classes registered for this site
     *
     * @var array
     */
    protected $resources = [];
    
    /**
     * @param   string  $siteKey
     */
    public function __construct(string $siteKey)
    {
        $this->siteKey = $siteKey;
    }
    
    /**
     * Registers a new resource class that should be available on this site.
     * A resource is basically an abstract repository to map any kind of data to a unique
     * output format served by the ResourceRepository or the resource api
     *
     * @param   string       $resourceClass      The class that is used as resource. It has to implement the ResourceInterface,
     *                                           or could simply extend the AbstractResource class
     * @param   string|null  $resourceType       A unique (in the current site) identifier for the resource. If omitted a resource
     *                                           type will be auto-generated based on the class name.
     * @param   array|null   $options            Optional options to be provided to the resource when handling requests.
     *                                           The list of options depend on the resource implementation you use.
     *
     * @return $this
     */
    public function register(string $resourceClass, ?string $resourceType = null, ?array $options = null): self
    {
        if (! in_array(ResourceInterface::class, class_implements($resourceClass), true)) {
            throw new InvalidArgumentException(
                'The given resource class: "' . $resourceClass . '" does not implement the required interface: ' .
                ResourceInterface::class
            );
        }
        
        if (empty($resourceType)) {
            $resourceType = $this->inflectResourceType($resourceClass);
        }
        
        if (isset($this->resources[$resourceType])) {
            throw new InvalidArgumentException('Failed to register resource "' . $resourceType . '" with class "' . $resourceClass .
                                               '", because a resource with the same name was already registered for site: ' . $this->siteKey);
        }
        
        $this->resources[$resourceType] = [
            'class' => $resourceClass,
            'options' => $options,
        ];
        
        return $this;
    }
    
    /**
     * Removes a previously registered resource class from the list.
     *
     * @param   string  $resourceType  The name of the resource to remove
     *
     * @return $this
     */
    public function remove(string $resourceType): self
    {
        unset($this->resources[$resourceType]);
        
        return $this;
    }
    
    /**
     * Returns the names of all registered resources
     *
     * @return array
     */
    public function getAll(): array
    {
        return array_keys($this->resources);
    }
    
    /**
     * Returns an array of the resource class and options for the given resource type.
     * Will return null if the given class is not registered as resource
     *
     * @param   string  $resourceType
     *
     * @return array|null
     */
    public function get(string $resourceType): ?array
    {
        return $this->resources[$resourceType] ?? null;
    }
    
    /**
     * Internal helper to generate the resource type for the resource class given
     *
     * @param   string  $resourceClass
     *
     * @return string
     */
    protected function inflectResourceType(string $resourceClass): string
    {
        if (in_array(ElementKeyProviderInterface::class, class_implements($resourceClass), true)) {
            return call_user_func([$resourceClass, 'getElementKey']);
        }
        
        return Inflector::toCamelBack(
            preg_replace('~(?:Resource)?(?:Provider)?(?:Override)?$~i', '', Path::classBasename($resourceClass))
        );
    }
}