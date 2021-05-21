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
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Constraint\ConstraintInterface;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use Neunerlei\Arrays\Arrays;
use Neunerlei\PathUtil\Path;

class FilterConstraint implements ConstraintInterface
{
    /**
     * The list of allowed filter properties. All undefined properties will be ignored
     *
     * @var array
     */
    protected $allowedProperties;
    
    /**
     * @var array
     */
    protected $likeFields;
    
    public function __construct(array $allowedProperties, array $likeProperties = [])
    {
        $this->allowedProperties = $allowedProperties;
        $this->likeFields = $likeProperties;
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
        // Combine the like fields into the allowed fields
        $allowedFields = array_unique(
            array_merge(
                array_values($this->allowedProperties),
                array_values($this->likeFields)
            )
        );
        
        // Get the filter
        $localWhere = [];
        foreach ($resourceQuery->getFilter() as $field => $options) {
            if (! in_array($field, $allowedFields, true)) {
                continue;
            }
            
            // Special handling for the pid field
            if ($field === 'pid') {
                $query = $query->withPids(array_map('intval', Arrays::makeFromStringList($options)));
                continue;
            }
            
            // Default handling
            $optionParts = Arrays::makeFromStringList($options);
            $optionWhere = [];
            foreach ($optionParts as $value) {
                if (! empty($optionWhere)) {
                    $optionWhere[] = 'OR';
                }
                
                if (in_array($field, $this->likeFields, true)) {
                    $optionWhere[] = [$field . ' LIKE' => '%' . $value . '%'];
                } else {
                    $optionWhere[] = [$field => $value];
                }
            }
            
            if (empty($optionWhere)) {
                continue;
            }
            
            $localWhere[] = $optionWhere;
        }
        
        // Update the queries where if required
        if (empty($localWhere)) {
            return $query;
        }
        
        return $query->withWhere(
            $localWhere,
            Path::classBasename(__CLASS__) . '_' . spl_object_id($this)
        );
        
    }
    
}