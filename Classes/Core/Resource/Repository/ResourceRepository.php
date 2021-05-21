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
 * Last modified: 2021.05.20 at 16:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceBackend;
use TYPO3\CMS\Core\SingletonInterface;

class ResourceRepository implements PublicServiceInterface, SingletonInterface
{
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $context;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceBackend
     */
    protected $backend;
    
    public function __construct(TypoContext $context, EnvironmentSimulator $simulator, ResourceBackend $backend)
    {
        $this->context = $context;
        $this->simulator = $simulator;
        $this->backend = $backend;
    }
    
    /**
     * Retrieves the data for a single resource entry from the repository
     *
     * @param   mixed       $resourceType  Either the name of a class, or an object that represents the resource type to find
     * @param   string|int  $id            The unique id of the resource to find
     * @param   array       $options       Additional options to apply when resolving the resource
     *                                     - pid int: Can be used to change the page id of the executed process.
     *                                     If this is left empty the current page id is used
     *                                     - language int|string|SiteLanguage: The language to set the environment to.
     *                                     Either as sys_language_uid value, as iso code or as language object
     *                                     - site string: Can be set to a valid site identifier to simulate the request
     *                                     on a specific TYPO3 site.
     *                                     - asAdmin bool (FALSE): Take a look at "asAdmin" here:
     *                                     {@link EnvironmentSimulator::runWithEnvironment()}
     *
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceItem|null
     */
    public function getResource($resourceType, $id, array $options = []): ?ResourceItem
    {
        return $this->runInSimulation(function () use ($resourceType, $id) {
            $config = $this->context->resource()->getResourceConfig($resourceType);
            if ($config === null) {
                return null;
            }
            
            return $this->backend->getResource($id, $config);
        }, $options);
    }
    
    public function getCollection($resourceType, ?array $query = null, array $options = []): ResourceCollection
    {
        return $this->runInSimulation(function () use ($resourceType, $query) {
            $config = $this->context->resource()->getResourceConfig($resourceType);
            
            if ($config === null) {
                return $this->backend->getEmptyCollection();
            }
            
            return $this->backend->getCollection($query, $config);
        }, $options);
    }
    
    /**
     * Internal helper to extract potential environment constraints from the query options
     * and run the given callback with these options applied
     *
     * @param   callable  $callback  The callback to execute
     * @param   array     $options   The options provided to the query method
     *
     * @return mixed|null
     */
    protected function runInSimulation(callable $callback, array &$options)
    {
        $simulatorOptions = [];
        
        foreach (['pid', 'language', 'site', 'asAdmin'] as $field) {
            if (isset($options[$field])) {
                $simulatorOptions[$field] = $options[$field];
            }
            unset($options[$field]);
        }
        
        return $this->simulator->runWithEnvironment($simulatorOptions, $callback);
    }
}