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
 * Last modified: 2021.05.21 at 19:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository\Constraint;


use LaborDigital\T3ba\Tool\Database\BetterQuery\AbstractBetterQuery;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;

interface ConstraintInterface
{
    /**
     * MUST process the given resource query and apply needed constraints to the given better query object.
     * The modified query object MUST be returned.
     *
     * @param   AbstractBetterQuery        $query          The database query to apply the constraint to
     * @param   ResourceQuery              $resourceQuery  The given resource query
     * @param   ResourceCollectionContext  $context        The collection context object
     *
     * @return \LaborDigital\T3ba\Tool\Database\BetterQuery\AbstractBetterQuery
     */
    public function apply(
        AbstractBetterQuery $query,
        ResourceQuery $resourceQuery,
        ResourceCollectionContext $context
    ): AbstractBetterQuery;
}