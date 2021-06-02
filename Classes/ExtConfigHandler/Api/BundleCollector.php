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
 * Last modified: 2021.06.02 at 20:35
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;

class BundleCollector implements NoDiInterface
{
    /**
     * The list of all registered bundle classes to load
     *
     * @var array
     */
    protected $bundles = [];
    
    public function __construct(array $defaultBundles)
    {
        $this->bundles = $defaultBundles;
    }
    
    /**
     * Registers a new bundle class in the list.
     * If the same class already exists, it will be overwritten
     *
     * @param   string      $bundleClass  The name of the bundle class that should be registered
     * @param   array|null  $options      Please check the documentation of the bundle to see if config options are supported.
     *
     * @return $this
     * @see \LaborDigital\T3fa\ExtConfigHandler\Api\ApiBundleInterface
     */
    public function register(string $bundleClass, ?array $options = null): self
    {
        if (! class_exists($bundleClass)) {
            throw new InvalidArgumentException('The given bundle class: "' . $bundleClass . '" does not exist');
        }
        
        if (! in_array(ApiBundleInterface::class, class_implements($bundleClass), true)) {
            throw new InvalidArgumentException(
                'The given bundle class: "' . $bundleClass . '" does not implement the required interface: ' . ApiBundleInterface::class);
        }
        
        $this->bundles[$bundleClass] = $options ?? [];
        
        return $this;
    }
    
    /**
     * Removes a previously registered bundle class
     *
     * @param   string  $bundleClass
     *
     * @return $this
     */
    public function remove(string $bundleClass): self
    {
        unset($this->bundles[$bundleClass]);
        
        return $this;
    }
    
    /**
     * Returns the names of all registered bundle classes
     *
     * @return array
     */
    public function getAll(): array
    {
        return array_keys($this->bundles);
    }
    
    /**
     * Returns the configured options for a bundle class, or an empty array if non were found
     *
     * @param   string  $bundleClass
     *
     * @return array
     */
    public function getOptions(string $bundleClass): array
    {
        return $this->bundles[$bundleClass] ?? [];
    }
}