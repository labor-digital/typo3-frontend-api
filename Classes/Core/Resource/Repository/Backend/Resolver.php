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
 * Last modified: 2021.06.10 at 10:15
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Backend;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use LaborDigital\T3fa\Core\Resource\Exception\InvalidConfigException;
use LaborDigital\T3fa\Core\Resource\Exception\PaginationException;
use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use LaborDigital\T3fa\Core\Resource\Query\QueryDefaults;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\Paginator;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;
use LaborDigital\T3fa\Core\Resource\ResourceInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformUtil;

class Resolver
{
    use ContainerAwareTrait;
    use TypoContextAwareTrait;
    use T3faCacheAwareTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Pagination\Paginator
     */
    protected $paginator;
    
    public function __construct(Paginator $paginator)
    {
        $this->paginator = $paginator;
    }
    
    /**
     * Resolves the raw data for a single resource
     *
     * @param   mixed  $id      Anything that can be used as id by your resource
     * @param   array  $config  The resource configuration array
     *
     * @return array|null|ResourceItem Null if the resource was not found, an array where [0] is the data
     *                                 [1] is the resource type, and [2] additional meta data,
     *                                 or a ResourceItem that was returned from the resource method
     * @throws \LaborDigital\T3fa\Core\Resource\Exception\InvalidConfigException
     */
    public function getResourceData($id, array $config)
    {
        $context = $this->makeInstance(
            ResourceContext::class,
            [$this->getTypoContext(), $config]
        );
        
        try {
            $data = $this->makeResourceInstance($config)->findSingle($id, $context);
        } catch (ResourceNotFoundException $e) {
            return null;
        }
        
        if ($data instanceof ResourceItem) {
            return $data;
        }
        
        $data = AutoTransformUtil::unifyValue($data);
        
        switch ($this->getCountOf($data)) {
            case 0:
                return null;
            case 1:
                $this->runInCacheScope(function (Scope $scope) use ($data) {
                    $scope->addCacheTag($data);
                });
                
                return [$data, $config['type'], $context->getMeta()];
        }
        
        throw new InvalidConfigException('The resource handler for: "' . $config['type'] . '" returned more than one resource data source');
    }
    
    /**
     * Resolves the data for a resource collection
     *
     * @param   array|null  $query   The resource query array to narrow down the result items
     * @param   array       $config  The resource configuration array
     *
     * @return array|ResourceCollection An array where [0] is the gathered resource data,
     *                                  [1] is the type, [2] is additional meta data and [3] either a Pagination object or null,
     *                                  or a ResourceCollection that was returned from the resource method
     */
    public function getCollectionData(?array $query, array $config)
    {
        $resourceQuery = $this->makeInstance(
            ResourceQuery::class,
            [$config['type'], $query ?? [], $config['defaultQuery'] ?? []]
        );
        
        $context = $this->makeInstance(
            ResourceCollectionContext::class,
            [$this->getTypoContext(), $config, $resourceQuery]
        );
        
        // The page size in the query wins over the page size that was given in the resource config
        // But can be overwritten by the collection handler method.
        if ($resourceQuery->getPageSize() !== QueryDefaults::$pageSize) {
            $context->setPageSize($resourceQuery->getPageSize());
        }
        
        try {
            $data = $this->makeResourceInstance($config)->findCollection($resourceQuery, $context);
        } catch (ResourceNotFoundException $e) {
            return [[], $config['type'], $context->getMeta(), null];
        }
        
        if ($data instanceof ResourceCollection) {
            return $data;
        }
        
        $data = AutoTransformUtil::unifyValue($data);
        
        [$slicedData, $pagination] = $this->paginator
            ->paginate($data, $resourceQuery->getPage(), $context->getPageSize(), $context->getPageFinder());
        
        $this->runInCacheScope(function (Scope $scope) use ($slicedData) {
            foreach ($slicedData as $data) {
                $scope->addCacheTag($data);
            }
        });
        
        return [$slicedData, $config['type'], $context->getMeta(), $pagination];
    }
    
    /**
     * Count the data items to check if we got a list where we should only get a single value
     *
     * @param $data
     *
     * @return int
     */
    protected function getCountOf($data): int
    {
        if (empty($data)) {
            return 0;
        }
        
        if (! is_iterable($data)) {
            return 1;
        }
        
        try {
            return $this->paginator->getItemCount($data);
        } catch (PaginationException $exception) {
            return 1;
        }
    }
    
    /**
     * Resolves and creates the resource instance based on the given configuration
     *
     * @param   array  $config
     *
     * @return \LaborDigital\T3fa\Core\Resource\ResourceInterface
     * @throws \LaborDigital\T3fa\Core\Resource\Exception\InvalidConfigException
     */
    protected function makeResourceInstance(array $config): ResourceInterface
    {
        if (empty($config['class']) || ! is_string($config['class']) || ! class_exists($config['class'])) {
            throw new InvalidConfigException('The resource configuration of "' . $config['type'] . '" is invalid, the resource class is not defined, or does not exist');
        }
        
        if (! $this->getContainer()->has($config['class'])) {
            return $this->makeInstance($config['class']);
        }
        
        return $this->getService($config['class']);
    }
}