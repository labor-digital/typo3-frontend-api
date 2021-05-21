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
 * Last modified: 2020.05.20 at 15:50
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Pagination;

/**
 * Interface SelfPaginatingInterface
 *
 * Allows you to control the pagination for resource object collections.
 * This is useful for implementing API requests on external services or generated data.
 *
 * PAGE FINDER: If you want to use the page finder make sure that your object
 * also implements the PageFinderAwareSelfPaginationInterface
 *
 * @package LaborDigital\Typo3FrontendApi\JsonApi\Pagination
 * @see     \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\PageFinderAwareSelfPaginatingInterface
 * @see     \LaborDigital\Typo3FrontendApi\JsonApi\Pagination\LateCountingSelfPaginatingInterface
 */
interface SelfPaginatingInterface
{

    /**
     * MUST return the list of items for a slice of the returned data based on the $offset and the $limit
     *
     * @param   int  $offset  Similar to a SQL Offset, the absolute number of ITEMS (not pages) that should be skipped
     * @param   int  $limit   Similar to a SQL Limit, the number of ITEMS that should be return for the current page
     *
     * @return iterable
     */
    public function getItemsFor(int $offset, int $limit): iterable;

    /**
     * MUST return the number of all ITEMS (not pages) that could be found in the paginated data collection
     *
     * @return int
     */
    public function getItemCount(): int;

}
