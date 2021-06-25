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
 * Last modified: 2021.06.25 at 20:04
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository;


use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\Pagination;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\PaginationAdapter;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceAbstract;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ResourceCollection extends AbstractResourceElement implements \Iterator
{
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Pagination\Pagination
     */
    protected $pagination;
    
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;
    
    /**
     * If the collection has been converted into an array of resource items,
     * the result will be stored here to avoid multiple transformations
     *
     * @var array|null
     */
    protected $resourceArray;
    
    /**
     * The iterator pointer
     *
     * @var int
     */
    protected $pointer;
    
    public function __construct(
        string $resourceType,
        ?iterable $raw,
        ?array $meta,
        string $baseUrl,
        string $linkQueryParams,
        Pagination $pagination,
        ResourceFactory $resourceFactory,
        TransformerFactory $transformerFactory
    )
    {
        parent::__construct($resourceType, $raw, $meta, $baseUrl, $linkQueryParams, $transformerFactory);
        $this->pagination = $pagination;
        $this->resourceFactory = $resourceFactory;
    }
    
    /**
     * Returns the list of all resource items as an array
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\ResourceItem[]
     */
    public function getItems(): array
    {
        // Similar to the caching inside of handleTransformation() method, this saves quite a bit of performance
        // but bloats memory usage which is not so nice :/ Again, I keep it until someone complains
        if (isset($this->resourceArray)) {
            return $this->resourceArray;
        }
        
        $this->resourceArray = [];
        foreach ($this->raw as $item) {
            $this->resourceArray[] = $this->resourceFactory->makeResourceItem($item, $this->getResourceType());
        }
        
        return $this->resourceArray;
    }
    
    /**
     * Returns the fractal resource collection that represents this collection
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function getFractalCollection(): Collection
    {
        $collection = GeneralUtility::makeInstance(
            Collection::class,
            $this->raw,
            $this->transformerFactory->getTransformer($this->getFirstResource(), false),
            $this->resourceType
        );
        
        if ($this->getMeta() !== null) {
            $collection->setMeta($this->getMeta());
        }
        
        $collection->setPaginator(
            GeneralUtility::makeInstance(
                PaginationAdapter::class,
                $this->pagination
            )
        );
        
        return $collection;
    }
    
    /**
     * Returns pagination information of of the collection, like the page, page size or item count
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\Pagination\Pagination
     */
    public function getPagination(): Pagination
    {
        return $this->pagination;
    }
    
    /**
     * Internal helper to retrieve the first resource item or null if this collection is empty
     *
     * @return mixed|null
     */
    protected function getFirstResource()
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->raw as $item) {
            return $item;
        }
        
        return null;
    }
    
    /**
     * @inheritDoc
     */
    protected function getFractalElement(): ResourceAbstract
    {
        return $this->getFractalCollection();
    }
    
    
    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->getItems()[$this->pointer];
    }
    
    /**
     * @inheritDoc
     */
    public function next()
    {
        $this->pointer++;
    }
    
    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->pointer;
    }
    
    /**
     * @inheritDoc
     */
    public function valid()
    {
        return isset($this->getItems()[$this->pointer]);
    }
    
    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->pointer = 0;
    }
}