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
 * Last modified: 2021.05.31 at 13:49
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Pagination;


use LaborDigital\T3ba\Core\Di\NoDiInterface;

class Pagination implements NoDiInterface
{
    /**
     * The current page we are showing
     *
     * @var int
     */
    public $page;
    
    /**
     * The number of all pages we have
     *
     * @var int
     */
    public $pages;
    
    /**
     * The maximum number of items on a single page
     *
     * @var int
     */
    public $pageSize;
    
    /**
     * The number of items on the current page
     *
     * @var int
     */
    public $pageCount;
    
    /**
     * The number of all items in the set
     *
     * @var int
     */
    public $itemCount;
    
    /**
     * Internal property to store the pagination request query string for the fractal paginatior
     *
     * @internal
     * @var string|null
     */
    public $paginationLink;
}