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


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3fa\Core\Resource\Exception\PaginationException;
use Neunerlei\Arrays\Arrays;
use Traversable;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class Paginator
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Pagination\Slicer
     */
    protected $slicer;
    
    public function __construct(Slicer $slicer)
    {
        $this->slicer = $slicer;
    }
    
    /**
     * Collects pagination information about the given $raw data and automatically slices the data to match the requirements
     *
     * @param   iterable|SelfPaginatingInterface  $raw         The raw data to be paginated
     * @param   int                               $queryPage   The page that was selected via the resource query
     * @param   int                               $pageSize    The maximal items per page
     * @param   callable|null                     $pageFinder  Optional page finder callback to resolve the correct page id if a specific element in the list was required
     *
     * @return array
     */
    public function paginate($raw, int $queryPage, int $pageSize, ?callable $pageFinder): array
    {
        $pagination = $this->makeInstance(Pagination::class);
        $pagination->pageSize = $pageSize;
        $pagination->itemCount = $this->getItemCount($raw);
        $pagination->pages = (int)max(ceil($pagination->itemCount / $pagination->pageSize), 1);
        $pagination->page = (int)max(min($pagination->pages, $this->findPage($raw, $queryPage, $pageSize, $pageFinder)), 1);
        
        if ($pagination->itemCount <= $pageSize) {
            // Shortcut -> no slicing required if the count is less or equal to a page size
            $slice = $raw;
            $pagination->pageCount = $pagination->itemCount;
        } else {
            // Slice the result based on the page size
            $slice = $this->slicer->slice($raw, $pagination);
        }
        
        return [$slice, $pagination];
    }
    
    /**
     * Tries multiple different options to retrieve the item count of the given value.
     *
     * @param   iterable|SelfPaginatingInterface  $raw  The value to retrieve the item count of
     *
     * @return int
     * @throws \LaborDigital\T3fa\Core\Resource\Exception\PaginationException
     */
    public function getItemCount($raw): int
    {
        if ($raw instanceof QueryResultInterface) {
            return $raw->count();
        }
        
        if ($raw instanceof SelfPaginatingInterface) {
            return 999999999;
        }
        
        if (is_object($raw) && method_exists($raw, 'count')) {
            return $raw->count();
        }
        
        if (is_array($raw)) {
            if (Arrays::isArrayList($raw) || Arrays::isSequential($raw)) {
                return count($raw);
            }
            
            throw new PaginationException('Failed to paginate the given set, that is an array but neither an array list nor a sequential array!');
        }
        
        if ($raw instanceof Traversable) {
            return iterator_count($raw);
        }
        
        return 1;
    }
    
    /**
     * Is used internally to resolve the current page number
     *
     * @param   iterable|SelfPaginatingInterface  $raw
     * @param   int                               $queryPage
     * @param   int                               $pageSize
     * @param   callable|null                     $pageFinder
     *
     * @return int
     * @throws \LaborDigital\T3fa\Core\Resource\Exception\PaginationException
     */
    protected function findPage($raw, int $queryPage, int $pageSize, ?callable $pageFinder): int
    {
        $queryPage = $queryPage ?? 1;
        
        if ($pageFinder === null) {
            return $queryPage;
        }
        
        if ($raw instanceof SelfPaginatingInterface) {
            if (! $raw instanceof PageFinderAwareSelfPaginatingInterface) {
                throw new PaginationException(
                    'Your self-paginating set of type: ' . get_class($raw) .
                    ' tries to use the "PageFinder" without implementing the required interface: ' .
                    PageFinderAwareSelfPaginatingInterface::class);
            }
            
            $raw = $raw->getAllItems();
        }
        
        if (! is_iterable($raw)) {
            return $queryPage;
        }
        
        $itemsPerPage = $pageSize;
        $page = 1;
        $itemOnPage = 0;
        foreach ($raw as $item) {
            if (call_user_func($pageFinder, $item, $page, $itemOnPage, $itemsPerPage) === true) {
                return $page;
            }
            
            if (++$itemOnPage >= $itemsPerPage) {
                ++$page;
                $itemOnPage = 0;
            }
        }
        
        return 1;
    }
}