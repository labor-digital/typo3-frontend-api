<?php
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.08.08 at 12:55
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Pagination;


use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class Paginator
{

    /**
     * The set of data we have to paginate
     *
     * @var Traversable|iterable|\LaborDigital\Typo3FrontendApi\JsonApi\Pagination\SelfPaginatingInterface
     */
    protected $set;

    /**
     * The number of items that are showed on a single page
     *
     * @var int
     */
    protected $pageSize = 30;

    /**
     * The page finder callback that is used to dynamically search the right page for a certain search field
     *
     * @var callable
     */
    protected $pageFinder;

    /**
     * Paginator constructor.
     *
     * @param   \Traversable  $set
     */
    public function __construct($set)
    {
        $this->set = $set;
    }

    /**
     * Returns the number of items per page
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Sets the number of items per page
     *
     * @param   int  $pageSize
     *
     * @return Paginator
     */
    public function setPageSize(int $pageSize): Paginator
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * Can be used to define a function that is called once for each element of the collection.
     * It is used to find the page number of an element in a list. This is useful if you just have an id
     * of a collection item and want to show the results on its page.
     *
     * Once this function returns true the current page is used for the output
     *
     * @param   callable  $pageFinder
     */
    public function setPageFinder(callable $pageFinder)
    {
        $this->pageFinder = $pageFinder;
    }

    /**
     * Runs the pagination of the currently given set, according to the current configuration.
     *
     * @param   int|null  $page  The page to display. If empty the first page will be displayed
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\Pagination
     */
    public function paginate(?int $page = null): Pagination
    {
        $pagination            = new Pagination();
        $pagination->pageSize  = $this->pageSize;
        $pagination->itemCount = $this->getItemCount();
        $pagination->pages     = max(ceil($pagination->itemCount / $pagination->pageSize), 1);
        $pagination->page      = max(min($pagination->pages, $this->applyPageFinder($page)), 1);
        $this->sliceSet($pagination);

        return $pagination;
    }

    /**
     * The same as paginate() but receives a request interface to read the configuration from.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\Pagination
     */
    public function paginateByRequest(ServerRequestInterface $request): Pagination
    {
        $queryParams = $request->getQueryParams();
        $maxItems    = $this->getPageSize();
        $page        = (int)Arrays::getPath($queryParams, ["page", "number"], 0);
        $size        = (int)Arrays::getPath($queryParams, ["page", "size"], $maxItems);
        $size        = min(1000, max(1, $size));
        $this->setPageSize($size);

        return $this->paginate($page);
    }

    /**
     * Returns the total number of items in the current set
     *
     * @return int
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    public function getItemCount(): int
    {
        if ($this->set instanceof QueryResultInterface) {
            return $this->set->count();
        }
        if ($this->set instanceof SelfPaginatingInterface) {
            return 999999999;
        }
        if (is_object($this->set) && method_exists($this->set, "count")) {
            return $this->set->count();
        }
        if (is_array($this->set)) {
            if (Arrays::isArrayList($this->set) || Arrays::isSequential($this->set)) {
                return count($this->set);
            }
            throw new PaginationException("Failed to paginate the given set, that is an array but neither an array list nor a sequential array!");
        }
        if ($this->set instanceof Traversable) {
            return iterator_count($this->set);
        }

        return 1;
    }

    /**
     * Is used internally to run the page finder callable and scroll the cursor to the correct position if possible.
     *
     * @param   int|null  $suggestedPage
     *
     * @return int
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\PaginationException
     */
    protected function applyPageFinder(?int $suggestedPage): int
    {
        $suggestedPage = $suggestedPage ?? 1;
        if (! is_callable($this->pageFinder)) {
            return $suggestedPage;
        }
        $set = $this->set;
        if ($set instanceof SelfPaginatingInterface) {
            if (! $set instanceof PageFinderAwareSelfPaginatingInterface) {
                throw new PaginationException("Your self-paginating set of type: " . get_class($set) .
                                              " tries to use the \"PageFinder\" without implementing the required interface: " .
                                              PageFinderAwareSelfPaginatingInterface::class);
            }
            $set = $set->getAllItems();
        }
        if (! is_iterable($set)) {
            return $suggestedPage;
        }
        $itemsPerPage = $this->pageSize;
        $page         = 1;
        $itemOnPage   = 0;
        foreach ($set as $item) {
            if (call_user_func($this->pageFinder, $item, $page, $itemOnPage, $itemsPerPage) === true) {
                return $page;
            }
            if (++$itemOnPage >= $itemsPerPage) {
                ++$page;
                $itemOnPage = 0;
            }
        }

        return 1;
    }

    /**
     * Internal helper which is used to get the chunk of the current set that represents the page
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\Pagination  $pagination
     *
     * @return array
     * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
     */
    protected function sliceSet(Pagination $pagination)
    {
        $offset                = (int)max(0, $pagination->pageSize * ($pagination->page - 1));
        $pagination->pageCount = 0;
        if ($this->set instanceof QueryResultInterface) {
            // Handle query results
            $query = $this->set->getQuery();

            // Handle initial limit
            $initialLimit = $query->getLimit();
            $limit        = $pagination->pageSize;
            if (! empty($initialLimit)) {
                $limit = min($initialLimit - $offset, $limit);
            }

            // Handle initial offset
            $initialOffset = $query->getOffset();
            if (! empty($initialOffset)) {
                $offset += $initialOffset;
            }

            // Rerun the query
            $pagination->items = $this->set->getQuery()
                                           ->setLimit($limit)
                                           ->setOffset($offset)
                                           ->execute();

            $pagination->pageCount = $pagination->items->count();
        } elseif ($this->set instanceof SelfPaginatingInterface) {
            $isLateCounting = $this->set instanceof LateCountingSelfPaginatingInterface;

            /**
             * Helper to update the page and item counts based on the set
             *
             * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\SelfPaginatingInterface  $set
             * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\Pagination               $pagination
             */
            $countGenerator = static function (SelfPaginatingInterface $set, Pagination $pagination): void {
                $pagination->itemCount = $set->getItemCount();
                $pagination->pages     = max(ceil($pagination->itemCount / $pagination->pageSize), 1);
                $pagination->page      = max(min($pagination->pages, $pagination->page), 1);
            };

            if (! $isLateCounting) {
                $countGenerator($this->set, $pagination);
            }

            $slice = [];
            foreach (
                $this->set->getItemsFor(
                    ($isLateCounting
                        ? $offset
                        : $pagination->pageSize * ($pagination->page - 1)
                    ),
                    $pagination->pageSize
                ) as $item
            ) {
                $slice[] = $item;
            }
            $pagination->items     = $slice;
            $pagination->pageCount = count($slice);

            if ($isLateCounting) {
                $countGenerator($this->set, $pagination);
            }

        } elseif (is_array($this->set)) {
            // Handle arrays
            $pagination->items = array_slice($this->set, $offset, $pagination->pageSize);
            /** @noinspection PhpParamsInspection */
            $pagination->pageCount = count($pagination->items);
        } elseif ($this->set instanceof Traversable) {
            // Handle iterators
            $c         = 0;
            $itemsLeft = $pagination->pageSize;
            $slice     = [];
            foreach ($this->set as $k => $v) {
                if ($c++ < $offset) {
                    continue;
                }
                if ($itemsLeft-- <= 0) {
                    break;
                }
                $slice[$k] = $v;
            }
            $pagination->items     = $slice;
            $pagination->pageCount = count($pagination->items);
        } else {
            throw new PaginationException("Failed to paginate the result set, as it has an unsupported type! Only QueryResults, arrays, iterables or objects which implement " .
                                          SelfPaginatingInterface::class . " are allowed");
        }

        // Return scalar values
        return [$this->set];
    }
}
