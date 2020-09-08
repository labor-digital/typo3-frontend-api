<?php
/*
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
 * Last modified: 2020.09.08 at 13:47
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Pagination;

/**
 * Interface LateCountingSelfPaginatingInterface
 *
 * An extension for the SelfPaginating interface which tells the paginator to call getItemCount() after getItemsFor() was executed.
 * This can be really helpful if you get the number of all items as part of your api request you do in getItemsFor()
 *
 * @package LaborDigital\Typo3FrontendApi\JsonApi\Pagination
 */
interface LateCountingSelfPaginatingInterface
{

}
