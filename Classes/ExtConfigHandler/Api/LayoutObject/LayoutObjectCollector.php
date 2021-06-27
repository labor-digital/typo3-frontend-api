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
 * Last modified: 2021.06.21 at 12:15
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api\LayoutObject;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\LayoutObject\LayoutObjectInterface;
use Neunerlei\Configuration\State\ConfigState;
use Neunerlei\Options\Options;

class LayoutObjectCollector implements NoDiInterface
{
    /**
     * The list of registered layout object classes by their unique identifier
     *
     * @var array
     */
    protected $objects = [];
    
    /**
     * The list of registered options for each layout object class
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * Registers a new layout object generator class for the provided identifier.
     * Layout objects are meant for static parts of your page layout, like menus, breadcrumbs or login forms.
     * The AbstractLayoutObject class your generator may extend comes with a bunch of predefined methods to generate
     * the data for your daily needs.
     *
     * @param   string  $identifier  A unique identifier to find look this layout object up with.
     *                               The identifier must be url friendly. Like /api/resources/layoutObject/my-fancy-object
     * @param   string  $className   The class to use as generator for the layout object.
     *                               It must implement LayoutObjectInterface and COULD/SHOULD extend AbstractLayoutObject
     * @param   array   $options     Additional options for the object generation
     *                               - cachePerPid (FALSE): If set to true the layout object data is cached aware of the
     *                               pid it was generated with. Meaning the object may look different on every page
     *                               - cachePerLayout (FALSE): If set to true the layout object data is cached aware of the
     *                               page layout(backend_layout). Meaning the object may look different on pages with different
     *                               layouts.
     *
     * @return $this
     * @see \LaborDigital\T3fa\Core\LayoutObject\AbstractLayoutObject
     * @see LayoutObjectInterface
     */
    public function registerObject(string $identifier, string $className, array $options = []): self
    {
        if (! class_exists($className) || ! in_array(LayoutObjectInterface::class, class_implements($className), true)) {
            throw new InvalidArgumentException('The given class: "' . $className . '" for layout object: "' . $identifier .
                                               '" either odes not exist, or does not implement the required interface: ' . LayoutObjectInterface::class);
        }
        
        $this->objects[$identifier] = $className;
        $this->options[$identifier] = Options::make($options, [
            'cachePerPid' => ['type' => 'bool', 'default' => false],
            'cachePerLayout' => ['type' => 'bool', 'default' => false],
        ]);
        
        return $this;
    }
    
    /**
     * Checks if a layout object with the given identifier exists.
     *
     * @param   string  $identifier
     *
     * @return bool
     */
    public function hasObject(string $identifier): bool
    {
        return isset($this->objects[$identifier]);
    }
    
    /**
     * Removes a previously registered object from the list of objects
     *
     * @param   string  $identifier
     *
     * @return $this
     */
    public function removeObject(string $identifier): self
    {
        unset($this->objects[$identifier], $this->options[$identifier]);
        
        return $this;
    }
    
    /**
     * Returns the list of all registered layout object generator classes by their unique id
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->objects;
    }
    
    /**
     * Returns the registered options provided for a single layout element
     *
     * @param   string  $identifier
     *
     * @return array|null
     */
    public function getOptions(string $identifier): ?array
    {
        return $this->options[$identifier] ?? null;
    }
    
    /**
     * Persists the local configuration into the provided config state object
     *
     * @param   \Neunerlei\Configuration\State\ConfigState  $state
     */
    public function finish(ConfigState $state): void
    {
        $state->set('objects', $this->objects);
        $state->set('options', $this->options);
    }
}