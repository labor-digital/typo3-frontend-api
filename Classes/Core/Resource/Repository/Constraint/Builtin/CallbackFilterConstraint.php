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


namespace LaborDigital\T3fa\Core\Resource\Repository\Constraint\Builtin;


use LaborDigital\T3ba\Tool\Database\BetterQuery\AbstractBetterQuery;
use LaborDigital\T3fa\Core\Resource\Exception\ConstraintException;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\ConstraintInterface;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;

class CallbackFilterConstraint implements ConstraintInterface
{
    /**
     * The property that should be filterable
     *
     * @var string
     */
    protected $property;
    
    /**
     * The callback to execute when a filter value is received
     *
     * @var callable
     */
    protected $callback;
    
    public function __construct(string $property, callable $callback)
    {
        $this->property = $property;
        $this->callback = $callback;
    }
    
    /**
     * @inheritDoc
     */
    public function apply(
        AbstractBetterQuery $query,
        ResourceQuery $resourceQuery,
        ResourceCollectionContext $context
    ): AbstractBetterQuery
    {
        if ($resourceQuery->getFilterValue($this->property) === null) {
            return $query;
        }
        
        $result = call_user_func($this->callback,
            $query,
            $resourceQuery->getFilterValue($this->property),
            $context,
            $this->property
        );
        
        if (! $result instanceof AbstractBetterQuery) {
            throw new ConstraintException('A constraint callback on property: "' . $this->property . '" (' . $context->getResourceType() . ') did not return a better query instance!');
        }
        
        return $result;
    }
    
}