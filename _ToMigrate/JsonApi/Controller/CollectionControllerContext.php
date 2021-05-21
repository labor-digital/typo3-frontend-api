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
 * Last modified: 2019.08.13 at 16:19
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Controller;


class CollectionControllerContext extends ResourceControllerContext
{

    /**
     * Holds either the page finder callback or null
     *
     * @var callable|null
     */
    protected $pageFinder;

    /**
     * Returns the number of items per page
     *
     * @var int
     */
    protected $pageSize = 30;

    /**
     * Returns the page finder callback
     *
     * @return callable|null
     */
    public function getPageFinder(): ?callable
    {
        return $this->pageFinder;
    }

    /**
     * Can be used to define a function that is called once for each element of the collection.
     * It is used to find the page number of an element in a list. This is useful if you just have an id
     * of a collection item and want to show the results on its page.
     *
     * Once this function returns TRUE the current page is used for the output and the search is stopped.
     *
     * @param   callable|null  $pageFinder  The callback that is called for every element in the collection.
     *                                      It MUST return TRUE (This is the searched item) or FALSE (keep looking) and
     *                                      receives the following arguments:
     *                                      - $item (The current item in the list)
     *                                      - $page (The page number to which we have currently crawled - based on the set page size)
     *                                      - $itemOnPage (The offset of the item on the current page)
     *                                      - $itemsPerPage (The given page size to return)
     *
     * @return CollectionControllerContext
     */
    public function setPageFinder(?callable $pageFinder): CollectionControllerContext
    {
        $this->pageFinder = $pageFinder;

        return $this;
    }

    /**
     * Returns the number of items per page
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return (int)$this->pageSize;
    }

    /**
     * Sets the number of items per page
     *
     * @param   int  $pageSize
     *
     * @return CollectionControllerContext
     */
    public function setPageSize(int $pageSize): CollectionControllerContext
    {
        $this->pageSize = $pageSize;

        return $this;
    }
}
