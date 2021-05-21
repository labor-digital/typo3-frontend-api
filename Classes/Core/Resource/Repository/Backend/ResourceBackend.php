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
 * Last modified: 2021.05.21 at 23:16
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Backend;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;

class ResourceBackend
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\Resolver
     */
    protected $resolver;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory
     */
    protected $resourceFactory;
    
    public function __construct(Resolver $resolver, ResourceFactory $resourceFactory)
    {
        $this->resolver = $resolver;
        $this->resourceFactory = $resourceFactory;
    }
    
    /**
     * Finds the data for a single resource request and returns it as a ResourceItem object.
     * If null is returned the resource with the id was not found
     *
     * @param   string|int  $id      The id of the resource to resolve
     * @param   array       $config  The resource configuration for the query
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceItem|null
     */
    public function getResource($id, array $config): ?ResourceItem
    {
        $data = $this->resolver->getResourceData($id, $config);
        
        if ($data === null) {
            return null;
        }
        
        if ($data instanceof ResourceItem) {
            return $data;
        }
        
        return $this->resourceFactory->makeResourceItem(...$data);
    }
    
    /**
     * Finds the data for a given resource query and returns the result as ResourceCollection object.
     *
     * @param   array|null  $query   The resource query array to narrow down the results
     * @param   array       $config  The resource configuration for the query
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection
     */
    public function getCollection(?array $query, array $config): ResourceCollection
    {
        $data = $this->resolver->getCollectionData($query, $config);
        
        if ($data instanceof ResourceCollection) {
            return $data;
        }
        
        if (empty($data[0])) {
            return $this->getEmptyCollection($config['type']);
        }
        
        return $this->resourceFactory->makeResourceCollection(...$data);
    }
    
    /**
     * Creates a new, empty collection object for the given resource type
     *
     * @param   string  $resourceType
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection
     */
    public function getEmptyCollection(string $resourceType): ResourceCollection
    {
        return $this->resourceFactory->makeResourceCollection([], $resourceType);
    }
}