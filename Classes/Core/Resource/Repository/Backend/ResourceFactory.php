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
 * Last modified: 2021.05.21 at 23:43
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Backend;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\OddsAndEnds\LazyLoadingUtil;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\Pagination;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\Paginator;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;

class ResourceFactory implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    public static $resourceItemClass = ResourceItem::class;
    public static $resourceCollectionClass = ResourceCollection::class;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory
     */
    protected $transformerFactory;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $context;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Pagination\Paginator
     */
    protected $paginator;
    
    public function __construct(TransformerFactory $transformerFactory, TypoContext $context, Paginator $paginator)
    {
        $this->transformerFactory = $transformerFactory;
        $this->context = $context;
        $this->paginator = $paginator;
    }
    
    /**
     * Creates a new resource item instance
     *
     * @param   mixed        $raw               The raw data that should be passed into the item
     * @param   string|null  $resourceType      The unique resource type name for the item to create.
     *                                          If this is NULL or not provided, the type is automatically resolved
     * @param   array|null   $meta              Optional metadata that should be stored for this item
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceItem
     */
    public function makeResourceItem($raw, ?string $resourceType = null, ?array $meta = null): ResourceItem
    {
        if ($raw instanceof ResourceItem) {
            return $raw;
        }
        
        if (empty($resourceType)) {
            $resourceType = $this->context->resource()->getResourceType($raw) ?? $this->makeResourceType($raw);
        }
        
        return $this->makeInstance(
            static::$resourceItemClass,
            [$resourceType, $raw, $meta, $this->transformerFactory]
        );
    }
    
    public function makeResourceCollection(iterable $raw, ?string $resourceType = null, ?array $meta = null, ?Pagination $pagination = null): ResourceCollection
    {
        if ($raw instanceof ResourceCollection) {
            return $raw;
        }
        
        if (empty($resourceType)) {
            $first = null;
            foreach ($raw as $item) {
                $first = $item;
                break;
            }
            
            $resourceType = $this->context->resource()->getResourceType($first) ?? $this->makeResourceType($first);
        }
        
        // Create a fallback pagination object
        if ($pagination === null) {
            [$_, $pagination] = $this->paginator->paginate(
                $raw, 1, $this->paginator->getItemCount($raw), null
            );
            
            dbge('fallback pagination', $pagination);
        }
        
        return $this->makeInstance(
            static::$resourceCollectionClass,
            [$resourceType, $raw, $meta, $pagination, $this, $this->transformerFactory]
        );
    }
    
    /**
     * Internal helper to create a dummy resource type for not configured values
     *
     * @param   $raw
     *
     * @return string
     */
    protected function makeResourceType($raw): string
    {
        $item = LazyLoadingUtil::getRealValue($raw);
        
        if (is_array($item)) {
            $item = reset($item);
        } elseif (is_iterable($item)) {
            /** @noinspection LoopWhichDoesNotLoopInspection */
            foreach ($item as $v) {
                $item = $v;
                break;
            }
        }
        
        $type = get_debug_type($item);
        
        $parts = array_filter(explode('\\', $type));
        $parts = array_map('ucfirst', array_filter(['auto', array_shift($parts), array_pop($parts)]));
        
        return lcfirst(implode($parts));
    }
}