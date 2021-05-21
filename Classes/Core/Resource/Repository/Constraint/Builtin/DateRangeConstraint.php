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
use LaborDigital\T3fa\Core\Resource\Exception\InvalidQueryException;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\ConstraintInterface;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use Neunerlei\PathUtil\Path;
use Neunerlei\TinyTimy\DateTimy;
use Throwable;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class DateRangeConstraint implements ConstraintInterface
{
    /**
     * The property that is used either as "start-date" (if $endDateProperty is set)
     * or as "date" which holds the dates (if $endDateProperty is NOT set)
     *
     * @var string
     */
    protected $dateProperty;
    
    /**
     * Optional property that is used as "end-date" if a resource has a date-range in itself
     *
     * @var string|null
     */
    protected $endDateProperty;
    
    public function __construct(string $dateProperty, ?string $endDateProperty = null)
    {
        $this->dateProperty = $dateProperty;
        $this->endDateProperty = $endDateProperty;
    }
    
    /**
     * @inheritDoc
     */
    public function apply(AbstractBetterQuery $query, ResourceQuery $resourceQuery, ResourceCollectionContext $context): AbstractBetterQuery
    {
        $dateRange = $resourceQuery->getFilterValue('dateRange');
        if ($dateRange === null) {
            return $query;
        }
        
        $startDate = new DateTimy(0);
        
        try {
            if (is_string($dateRange['start'] ?? null)) {
                $startDate = new DateTimy($dateRange['start']);
            }
        } catch (Throwable $e) {
            throw new InvalidQueryException('The date-range "start" value on resource: ' . $context->getResourceType() . ' is invalid!');
        }
        
        try {
            if (is_string($dateRange['end'] ?? null)) {
                $endDate = new DateTimy($dateRange['end']);
                
                // If a short format like YYYY-MM is used, we automatically set the end date to the last day of the month
                if (substr_count($dateRange['end'], '-') === 1) {
                    $endDate->modify('+1 month -1 second');
                }
            }
        } catch (Throwable $e) {
            throw new InvalidQueryException('The date-range "end" value on resource: ' . $context->getResourceType() . ' is invalid!');
        }
        
        if (! isset($endDate)) {
            $endDate = (clone $startDate)->setTime(23, 59, 59);
        }
        
        if ($startDate > $endDate) {
            throw new InvalidQueryException('The date-range "end" on resource: ' . $context->getResourceType() . ' has a higher value then "start" ');
        }
        
        return $query->withWhere(
            [
                function (QueryInterface $query) use ($startDate, $endDate) {
                    return $query->logicalAnd([
                        $query->greaterThanOrEqual($this->dateProperty, $startDate->formatSql()),
                        $query->lessThanOrEqual($this->endDateProperty ?? $this->dateProperty, $endDate->formatSql()),
                    ]);
                },
            ],
            Path::classBasename(__CLASS__) . '_' . spl_object_id($this)
        );
    }
    
}