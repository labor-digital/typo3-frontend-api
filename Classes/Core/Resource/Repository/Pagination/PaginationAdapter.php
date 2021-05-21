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
 * Last modified: 2021.05.22 at 00:16
 */

declare(strict_types=1);
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
 * Last modified: 2019.08.08 at 13:43
 */

namespace LaborDigital\T3fa\Core\Resource\Repository\Pagination;


use League\Fractal\Pagination\PaginatorInterface;

class PaginationAdapter implements PaginatorInterface
{
    
    /**
     * @var Pagination
     */
    protected $pagination;
    
    public function __construct(Pagination $pagination)
    {
        $this->pagination = $pagination;
    }
    
    /**
     * @inheritDoc
     */
    public function getCurrentPage()
    {
        return $this->pagination->page;
    }
    
    /**
     * @inheritDoc
     */
    public function getLastPage()
    {
        return $this->pagination->pages;
    }
    
    /**
     * @inheritDoc
     */
    public function getTotal()
    {
        return $this->pagination->pageCount;
    }
    
    /**
     * @inheritDoc
     */
    public function getCount()
    {
        return $this->pagination->itemCount;
    }
    
    /**
     * @inheritDoc
     */
    public function getPerPage()
    {
        return $this->pagination->pageSize;
    }
    
    /**
     * @inheritDoc
     */
    public function getUrl($page)
    {
        // Todo to implement this we should add implement the resource routing first
        return (string)$page;

//        $q = parse_query($this->link->getQuery());
//        $q["page[number]"] = $page;
//        $q = build_query($q);
//
//        return (string)$this->link->withQuery($q);
    }
    
}