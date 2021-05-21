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
 * Last modified: 2021.05.06 at 16:44
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Query;


use Neunerlei\Arrays\Arrays;

class ResourceQuery
{
    /**
     * The unique name of this resource
     *
     * @var string
     */
    protected $resourceType;
    
    /**
     * The validated, merge query combining $query and $defaultQuery
     *
     * @var array
     */
    protected $query;
    
    /**
     * ResourceQuery constructor.
     *
     * @param   string  $resourceType  The name of the resource this query is targeted at
     * @param   array   $query         The raw query array to be used in this query object
     * @param   array   $defaultQuery  IMPORTANT: $defaultQuery is EXPECTED to be parsed already!
     */
    public function __construct(string $resourceType, array $query, array $defaultQuery)
    {
        $this->resourceType = $resourceType;
        
        $this->query = Arrays::merge(
            $defaultQuery,
            Parser::parse($query),
            'nn', 'r'
        );
    }
    
    /**
     * Returns the resource type this query applies to
     *
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
    
    /**
     * Returns the given filter constraints
     *
     * @return array
     */
    public function getFilter(): array
    {
        return $this->query['filter'];
    }
    
    /**
     * Returns the given value for a single filter option.
     *
     * Example: &filter[foo]=bar&filter[bar][baz]=123
     *
     * @param   string  $name     Either the name of a filter option, or a path to a value separated with a period (.)
     * @param   null    $default  The default value to return if the filter name was not found
     *
     * @return array|mixed|null
     */
    public function getFilterValue(string $name, $default = null)
    {
        $v = Arrays::getPath($this->getFilter(), $name, $default);
        
        // Handle an empty string as NOT set
        return $v === '' ? $default : $v;
    }
    
    /**
     * Returns all requested fields by their resource type
     * If $resourceType is given, it returns all required fields for that specific type,
     * or null if there a no specific fields requested. If an empty array is returned NO fields are included
     *
     * @return array|null
     */
    public function getIncludedFields(?string $resourceType = null): ?array
    {
        if ($resourceType === null) {
            return empty($this->query['fields']) ? null : $this->query['fields'];
        }
        
        return $this->query['fields'][$resourceType] ?? null;
    }
    
    /**
     * Returns true if the required field is requested for the resource type.
     *
     * @param   string       $fieldName
     * @param   string|null  $resourceType
     *
     * @return bool
     */
    public function isIncludedField(string $fieldName, ?string $resourceType = null): bool
    {
        $fields = $this->getIncludedFields($resourceType ?? $this->resourceType);
        
        // No specific fields are required -> true
        if ($fields === null) {
            return true;
        }
        
        // No fields are required
        if (empty($fields)) {
            return false;
        }
        
        return in_array($fieldName, $fields, true);
    }
    
    /**
     * Returns the relationships included in this query
     *
     * @param   string|null  $path  If no path is given, the relationships of the base resource
     *                              are returned, if given the relationships of sub-resources can be returned
     *
     * @return array
     */
    public function getIncludedRelationships(?string $path = null): array
    {
        if ($path === null) {
            return $this->query['include'][''] ?? [];
        }
        
        $entries = Arrays::getPath(
            $this->query['include'],
            $path,
            []
        );
        
        return array_filter($entries, 'is_string');
    }
    
    /**
     * Checks if a given $fieldName is an included relationship
     *
     * @param   string       $fieldName  The name of an includeable relationship field in your base resource
     * @param   string|null  $path       Optional path to check for included fields on sub-resources
     *
     * @return bool
     */
    public function isIncludedRelationship(string $fieldName, ?string $path = null): bool
    {
        return $this->isWildcardInclude() || in_array($fieldName, $this->getIncludedRelationships($path), true);
    }
    
    /**
     * Returns true if ALL relationships should be included.
     * Can be triggered via include=*
     *
     * @return bool
     */
    public function isWildcardInclude(): bool
    {
        return ! empty($this->query['meta']['includeAll']);
    }
    
    /**
     * Returns the sorting configuration for this query.
     * The result is an array of $sortColumn => $direction,
     * where $direction is either "asc" or "desc" as a string.
     *
     * @return array
     */
    public function getSorting(): array
    {
        return $this->query['sort'];
    }
    
    /**
     * Returns the raw pagination options for this query
     *
     * @return array
     */
    public function getPagination(): array
    {
        return $this->query['page'];
    }
    
    
    /**
     * Returns the requested page number
     *
     * @return int
     */
    public function getPage(): int
    {
        return $this->query['page']['number'] ?? 1;
    }
    
    /**
     * Returns the requested page size
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->query['page']['size'] ?? QueryDefaults::$pageSize;
    }
    
    /**
     * Returns additional query parameter that have been given in the query, but don't fit the JSON:API schema
     *
     * @return array
     */
    public function getAdditional(): array
    {
        return $this->query['meta']['additional'];
    }
    
    /**
     * Returns the query content as a plain array
     *
     * @return array
     */
    public function asArray(): array
    {
        return Dumper::dump($this->query);
    }
}