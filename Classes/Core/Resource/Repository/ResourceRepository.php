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
 * Last modified: 2021.06.21 at 19:18
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
     * @param   array       $options       Additional options to simulate a specific environment:
     *                                     {@link EnvironmentSimulator::runWithEnvironment()}
     *
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceItem|null
     */
    public function getResource($resourceType, $id, array $options = []): ?ResourceItem
    {
        return $this->simulator->runWithEnvironment($options, function () use ($resourceType, $id) {
            $config = $this->context->resource()->getResourceConfig($resourceType);
            if ($config === null) {
                return null;
            }
            
            return $this->backend->getResource($id, $config);
        });
    }
    
    /**
     * The counterpart to getResource(). This method allows you to retrieve multiple resource items as a "collection".
     *
     * @param   mixed       $resourceType  Either the name of a class, or an object that represents the resource type to find
     * @param   array|null  $query         An optional resource query (in a similar syntax as your query parameters in the url)
     *                                     {@see https://jsonapi.org/format/#fetching}
     * @param   array       $options       Additional options to simulate a specific environment:
     *                                     {@link EnvironmentSimulator::runWithEnvironment()}
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection
     */
    public function getCollection($resourceType, ?array $query = null, array $options = []): ResourceCollection
    {
        return $this->simulator->runWithEnvironment($options, function () use ($resourceType, $query) {
            $config = $this->context->resource()->getResourceConfig($resourceType);
            
            if ($config === null) {
                return $this->backend->getEmptyCollection($config['type']);
            }
            
            return $this->backend->getCollection($query, $config);
        });
    }
}