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
 * Last modified: 2021.05.21 at 19:38
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Pagination;

/**
 * Interface LateCountingSelfPaginatingInterface
 *
 * An extension for the SelfPaginating interface which tells the paginator to call getItemCount() after getItemsFor() was executed.
 * This can be really helpful if you get the number of all items as part of your api request you do in getItemsFor()
 *
 * @package LaborDigital\T3fa\Core\Resource\Repository\Pagination
 */
interface LateCountingSelfPaginatingInterface
{

}
