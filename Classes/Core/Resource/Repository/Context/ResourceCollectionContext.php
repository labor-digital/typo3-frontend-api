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
 * Last modified: 2021.05.21 at 19:29
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Context;


use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Resource\Query\QueryDefaults;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\ConstraintBuilder;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;

class ResourceCollectionContext extends ResourceContext
{
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Query\ResourceQuery
     */
    protected $resourceQuery;
    
    /**
     * Holds either the page finder callback or null
     *
     * @var callable|null
     */
    protected $pageFinder;
    
    /**
     * Returns the number of items per page
     *
     * @var int|null
     */
    protected $pageSize;
    
    public function __construct(TypoContext $typoContext, array $config, ResourceQuery $resourceQuery)
    {
        parent::__construct($typoContext, $config);
        $this->resourceQuery = $resourceQuery;
        $this->pageSize = $config['pageSize'];
    }
    
    /**
     * The query which was given to
     *
     * @return \LaborDigital\T3fa\Core\Resource\Query\ResourceQuery
     */
    public function getQuery(): ResourceQuery
    {
        return $this->resourceQuery;
    }
    
    /**
     * The constraint builder provides proven solutions to narrow down your database query,
     * based on your resource query in a collection request.
     *
     * The builder is designed to work with BetterQuery objects and comes with some of the
     * most common constraint needs already built in.
     *
     * Simply create a new builder instance with this method,
     * add your constraints and call apply($query) on it. The result will be the modified query object.
     *
     * @return ConstraintBuilder
     */
    public function getConstraintBuilder(): ConstraintBuilder
    {
        return $this->typoContext->di()->makeInstance(
            ConstraintBuilder::class,
            [$this]
        );
    }
    
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
     * @return $this
     */
    public function setPageFinder(?callable $pageFinder): self
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
        return $this->pageSize ?? QueryDefaults::$pageSize;
    }
    
    /**
     * Sets the number of items per page
     *
     * @param   int|null  $pageSize  The page size or null to reset the size back to the defaults
     *
     * @return $this
     */
    public function setPageSize(?int $pageSize): self
    {
        $this->pageSize = max(1, min($pageSize ?? QueryDefaults::$pageSize, QueryDefaults::$maxPageSize));
        
        return $this;
    }
}