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
 * Last modified: 2021.05.25 at 10:56
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Pagination;


use LaborDigital\T3fa\Core\Resource\Exception\PaginationException;
use Traversable;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class Slicer
{
    /**
     * Extracts the required slice of data from the $raw set based on the instructions in $pagination
     *
     * @param   iterable|SelfPaginatingInterface  $raw         The raw data to extract the slice from
     * @param   Pagination                        $pagination  The prepared pagination object that describes how the slice should be retrieved
     *
     * @return iterable
     */
    public function slice($raw, Pagination $pagination): iterable
    {
        $offset = (int)max(0, $pagination->pageSize * ($pagination->page - 1));
        
        if ($raw instanceof QueryResultInterface) {
            return $this->sliceQueryResult($offset, $raw, $pagination);
        }
        
        if ($raw instanceof SelfPaginatingInterface) {
            return $this->sliceSelfPaginating($offset, $raw, $pagination);
        }
        
        if (is_array($raw)) {
            return $this->sliceArray($offset, $raw, $pagination);
        }
        
        if ($raw instanceof Traversable) {
            return $this->sliceTraversable($offset, $raw, $pagination);
        }
        
        throw new PaginationException(
            'Failed to paginate the result set, as it has an unsupported type! Only QueryResults, arrays, iterables or objects which implement ' .
            SelfPaginatingInterface::class . ' are allowed'
        );
    }
    
    protected function sliceQueryResult(int $offset, QueryResultInterface $raw, Pagination $pagination): iterable
    {
        $query = $raw->getQuery();
        
        $initialLimit = $query->getLimit();
        $limit = $pagination->pageSize;
        if (! empty($initialLimit)) {
            $limit = min($initialLimit - $offset, $limit);
        }
        
        $initialOffset = $query->getOffset();
        if (! empty($initialOffset)) {
            $offset += $initialOffset;
        }
        
        $slice = $raw->getQuery()
                     ->setLimit($limit)
                     ->setOffset($offset)
                     ->execute();
        
        $pagination->pageCount = $slice->count();
        
        return $slice;
    }
    
    protected function sliceSelfPaginating(int $offset, SelfPaginatingInterface $raw, Pagination $pagination): iterable
    {
        $isLateCounting = $raw instanceof LateCountingSelfPaginatingInterface;
        
        /**
         * Helper to update the page and item counts based on the set
         *
         * @param   SelfPaginatingInterface  $set
         * @param   Pagination               $pagination
         */
        $countGenerator = static function (SelfPaginatingInterface $raw, Pagination $pagination): void {
            $pagination->itemCount = $raw->getItemCount();
            $pagination->pages = (int)max(ceil($pagination->itemCount / $pagination->pageSize), 1);
            $pagination->page = (int)max(min($pagination->pages, $pagination->page), 1);
        };
        
        if (! $isLateCounting) {
            $countGenerator($raw, $pagination);
        }
        
        $slice = [];
        foreach (
            $raw->getItemsFor(
                ($isLateCounting
                    ? $offset
                    : $pagination->pageSize * ($pagination->page - 1)
                ),
                $pagination->pageSize
            ) as $item
        ) {
            $slice[] = $item;
        }
        
        $pagination->pageCount = count($slice);
        
        if ($isLateCounting) {
            $countGenerator($raw, $pagination);
        }
        
        return $slice;
    }
    
    protected function sliceArray(int $offset, array $raw, Pagination $pagination): iterable
    {
        $slice = array_slice($raw, $offset, $pagination->pageSize);
        $pagination->pageCount = count($slice);
        
        return $slice;
    }
    
    protected function sliceTraversable(int $offset, Traversable $raw, Pagination $pagination): iterable
    {
        $c = 0;
        $itemsLeft = $pagination->pageSize;
        $slice = [];
        
        foreach ($raw as $k => $v) {
            if ($c++ < $offset) {
                continue;
            }
            
            if ($itemsLeft-- <= 0) {
                break;
            }
            
            $slice[$k] = $v;
        }
        
        $pagination->pageCount = count($slice);
        
        return $slice;
    }
}