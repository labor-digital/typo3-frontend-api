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


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Tool\Database\BetterQuery\AbstractBetterQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\Builtin\CallbackFilterConstraint;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\Builtin\DateRangeConstraint;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\Builtin\FilterConstraint;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\Builtin\SortConstraint;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConstraintBuilder implements NoDiInterface
{
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext
     */
    protected $context;
    
    /**
     * The list of registered constraints that should be applied
     *
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Constraint\ConstraintInterface[]
     */
    protected $constraints = [];
    
    public function __construct(ResourceCollectionContext $context)
    {
        $this->context = $context;
    }
    
    /**
     * Adds a filter constraint to narrow down the collection list
     *
     * @param   array|null  $allowedProperties  The list of allowed filter properties. All undefined properties will be ignored
     * @param   array|null  $likeProperties     The list of allowed properties that should be treated as "LIKE" in your SQL database
     *
     * @return $this
     * @see https://jsonapi.org/format/#fetching-filtering
     */
    public function addFilterConstraint(?array $allowedProperties, ?array $likeProperties = null): self
    {
        return $this->addConstraint(
            GeneralUtility::makeInstance(
                FilterConstraint::class,
                $allowedProperties ?? [], $likeProperties ?? []
            )
        );
    }
    
    /**
     * Adds a filter constraint which executes a given callback when a filter value for the given $property was returned.
     *
     * @param   string    $property  The property this filter should listen to
     * @param   callable  $callback  The callback to execute when a filter value is present.
     *                               The callback receives the following arguments:
     *                               - AbstractBetterQuery $dbQuery
     *                               - mixed $filterValue
     *                               - ResourceCollectionContext $context
     *                               - string $nameOfTheProperty
     *                               It MUST return the modified $dbQuery as a result
     *
     * @return $this
     */
    public function addCallbackFilterConstraint(string $property, callable $callback): self
    {
        return $this->addConstraint(
            GeneralUtility::makeInstance(
                CallbackFilterConstraint::class,
                $property, $callback
            )
        );
    }
    
    /**
     * Adds a constraint that allows the sorting of resources in the collection based on a set of given properties.
     *
     * @param   array  $allowedProperties  A list of properties that are allowed to be used as sort fields
     *
     * @return $this
     */
    public function addSortConstraint(array $allowedProperties): self
    {
        return $this->addConstraint(
            GeneralUtility::makeInstance(
                SortConstraint::class,
                $allowedProperties
            )
        );
    }
    
    /**
     * Adds a constraint which allows to narrow the collection down to only contain values in a certain date range
     *
     * Note: Use filter[dateRange][start]=yyyy-mm-dd for the start date and filter[dateRange][end]=yyyy-mm-dd for the end date
     * If filter[dateRange][end] is omitted only entries on the same day as filter[dateRange][start] are returned
     *
     * @param   string       $dateProperty     The property that is used either as "start-date" (if $endDateProperty is set)
     *                                         or as "date" which holds the dates (if $endDateProperty is NOT set)
     * @param   string|null  $endDateProperty  Optional property that is used as "end-date" if a resource has a date-range in itself
     *
     * @return $this
     */
    public function addDateRangeConstraint(string $dateProperty, ?string $endDateProperty = null): self
    {
        return $this->addConstraint(
            GeneralUtility::makeInstance(
                DateRangeConstraint::class,
                $dateProperty, $endDateProperty
            )
        );
    }
    
    /**
     * Adds a new custom constraint object to the list of constraints to apply.
     * A constraint class must implement the ConstraintInterface.
     *
     * @param   ConstraintInterface  $constraint  The constraint instance to register
     *
     * @return $this
     */
    public function addConstraint(ConstraintInterface $constraint): self
    {
        $this->constraints[] = $constraint;
        
        return $this;
    }
    
    /**
     * Allows you to override all registered constraints with new ones.
     *
     * @param   ConstraintInterface[]  $constraints  The list of constraints to set
     *
     * @return $this
     */
    public function setConstraints(array $constraints): self
    {
        $this->constraints = [];
        
        array_map([$this, 'addConstraint'], $constraints);
        
        return $this;
    }
    
    /**
     * Returns all registered constraint objects
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\Constraint\ConstraintInterface[]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }
    
    /**
     * Removes all registered constraints
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->constraints = [];
        
        return $this;
    }
    
    /**
     * Applies all registered constraints to the given query object.
     *
     * @param   \LaborDigital\T3ba\Tool\Database\BetterQuery\AbstractBetterQuery  $query
     *
     * @return \LaborDigital\T3ba\Tool\Database\BetterQuery\AbstractBetterQuery
     */
    public function apply(AbstractBetterQuery $query): AbstractBetterQuery
    {
        foreach ($this->constraints as $constraint) {
            $query = $constraint->apply($query, $this->context->getQuery(), $this->context);
        }
        
        return $query;
    }
}