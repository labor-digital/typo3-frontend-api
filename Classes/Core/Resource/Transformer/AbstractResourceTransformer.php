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
 * Last modified: 2021.05.31 at 14:08
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceCollection;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceItem;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\Resource\ResourceAbstract;

abstract class AbstractResourceTransformer implements ResourceTransformerInterface
{
    use ContainerAwareTrait;
    use TypoContextAwareTrait;
    
    /**
     * The list of includeable fields that should be always present
     *
     * @var array
     */
    protected $defaultIncludes = [];
    
    /**
     * The list of fields that can be included via request
     *
     * @var array
     */
    protected $availableIncludes = [];
    
    /**
     * @inheritDoc
     */
    public function getAvailableIncludes(): array
    {
        return $this->availableIncludes;
    }
    
    /**
     * @inheritDoc
     */
    public function getDefaultIncludes(): array
    {
        return $this->defaultIncludes;
    }
    
    /**
     * Helper to create the "include" collection when building your own transformer
     *
     * @param $value
     *
     * @return \League\Fractal\Resource\Collection
     */
    protected function autoIncludeCollection($value): Collection
    {
        if ($value instanceof ResourceCollection) {
            return $value->getFractalCollection();
        }
        
        if ($value instanceof Collection) {
            return $value;
        }
        
        return $this->getResourceFactory()->makeResourceCollection($value)->getFractalCollection();
    }
    
    /**
     * Helper to create the "include" item when building your own transformer
     *
     * @param $value
     *
     * @return ResourceAbstract
     */
    protected function autoIncludeItem($value): ResourceAbstract
    {
        if ($value instanceof ResourceItem) {
            return $value->getFractalItem();
        }
        
        if ($value instanceof ResourceAbstract) {
            return $value;
        }
        
        if (empty($value)) {
            return $this->null();
        }
        
        return $this->getResourceFactory()->makeResourceItem($value)->getFractalItem();
    }
    
    /**
     * Create a new item resource object.
     *
     * @param   mixed                 $data
     * @param   TransformerInterface  $transformer
     * @param   string|null           $resourceKey
     *
     * @return Item
     */
    protected function item($data, TransformerInterface $transformer, ?string $resourceKey = null)
    {
        return $this->makeInstance(Item::class, [$data, $transformer, $resourceKey]);
    }
    
    /**
     * Create a new collection resource object.
     *
     * @param   mixed                 $data
     * @param   TransformerInterface  $transformer
     * @param   string|null           $resourceKey
     *
     * @return Collection
     */
    protected function collection($data, TransformerInterface $transformer, ?string $resourceKey = null)
    {
        return $this->makeInstance(Collection::class, [$data, $transformer, $resourceKey]);
    }
    
    /**
     * Create a new null resource object.
     *
     * @return NullResource
     */
    protected function null()
    {
        return $this->makeInstance(NullResource::class);
    }
    
    /**
     * Internal helper to retrieve the instance of the resource factory
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory
     * @internal
     */
    protected function getResourceFactory(): ResourceFactory
    {
        return $this->getService(ResourceFactory::class);
    }
}