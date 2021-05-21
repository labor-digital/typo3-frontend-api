<?php
declare(strict_types=1);
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.05.20 at 18:21
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Pagination;

/**
 * Interface PageFinderAwareSelfPaginationInterface
 *
 * An extension for the SelfPaginatingInterface to allow usage of the pageFinder for custom pagination objects
 *
 * @package LaborDigital\Typo3FrontendApi\JsonApi\Pagination
 * @see     \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\SelfPaginatingInterface
 */
interface PageFinderAwareSelfPaginatingInterface
{

    /**
     * MUST return a list of ALL items on ALL pages to iterate.
     * This is used to find the page of a certain object using the page finder
     *
     * @return iterable
     *
     * @see \LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext::setPageFinder()
     */
    public function getAllItems(): iterable;

}
