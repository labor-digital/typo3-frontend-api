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
 * Last modified: 2021.06.25 at 12:34
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Query;


use LaborDigital\T3fa\Core\Resource\Exception\InvalidQueryException;
use Neunerlei\Arrays\Arrays;

class Parser
{
    /**
     * Parses and validates the given resource query object based on the JSON:API definition
     *
     * A special "meta" node is returned that contains additional information:
     * - includeAll (bool): True if an asterisk was set as "include", which means all relationships should be included
     * - additional (array): Additional query parameters that don't fit the jsonApi request schema
     *
     * @param   array  $query
     *
     * @return array
     */
    public static function parse(array $query): array
    {
        $meta = [];
        
        static::extractAdditional($query, $meta);
        
        return [
            'filter' => static::parseFilter($query),
            'fields' => static::parseFields($query),
            'include' => static::parseInclude($query, $meta),
            'sort' => static::parseSorting($query),
            'page' => static::parsePage($query),
            'meta' => &$meta,
        ];
    }
    
    protected static function parseFilter(array $query): array
    {
        if (empty($query['filter'])) {
            return [];
        }
        
        if (! is_array($query['filter'])) {
            throw new InvalidQueryException('The "filter" parameter must be an array');
        }
        
        return $query['filter'];
    }
    
    protected static function parseFields(array $query): array
    {
        if (empty($query['fields'])) {
            return [];
        }
        
        if (! is_array($query['fields'])) {
            throw new InvalidQueryException('The "fields" parameter must be an array');
        }
        
        $fieldsClean = [];
        foreach ($query['fields'] as $fieldResourceType => $fields) {
            if (empty($fields)) {
                $fieldsClean[$fieldResourceType] = [];
                continue;
            }
            
            if (! is_string($fields)) {
                if (! is_array($fields)) {
                    throw new InvalidQueryException('The "fields" parameter of resource type: "' . $fieldResourceType . '" must be a string');
                }
                
                // While given via PHP we allow providing the fields as an array
                // @todo check if this works correctly
                $fieldsClean[$fieldResourceType] = array_filter($fields, 'is_string');
                continue;
            }
            
            $fieldsClean[$fieldResourceType] = array_unique(Arrays::makeFromStringList($fields));
        }
        
        return $fieldsClean;
    }
    
    protected static function parseInclude(array $query, array &$meta): array
    {
        if (empty($query['include'])) {
            return [];
        }
        
        if ($query['include'] === '*') {
            $meta['includeAll'] = true;
            
            return [];
        }
        
        if (! is_string($query['include'])) {
            if (! is_array($query['include'])) {
                throw new InvalidQueryException('The "include" parameter must be a string');
            }
            
            // While given via PHP we allow providing the fields as an array
            $paths = $query['include'];
        }
        
        if (! isset($paths)) {
            $paths = Arrays::makeFromStringList($query['include']);
        }
        
        $clean = [];
        foreach ($paths as $path) {
            $parts = Arrays::parsePath($path);
            $fieldName = array_pop($parts);
            
            if (empty($fieldName)) {
                continue;
            }
            
            if (empty($parts)) {
                $clean[''][] = $fieldName;
                continue;
            }
            
            $pathFields = Arrays::getPath($clean, $parts, []);
            $clean = Arrays::setPath($clean, $parts, array_unique(
                array_merge(
                    $pathFields,
                    [$fieldName]
                )
            ));
        }
        
        return $clean;
    }
    
    protected static function parseSorting(array $query): array
    {
        if (empty($query['sort'])) {
            return [];
        }
        
        if (! is_string($query['sort'])) {
            if (! is_array($query['sort'])) {
                throw new InvalidQueryException('The "sort" parameter must be a string');
            }
            
            // While given via PHP we allow providing the fields as an array
            $items = $query['sort'];
            foreach ($items as $field => $direction) {
                if ($direction !== 'desc' && $direction !== 'asc') {
                    throw new InvalidQueryException(
                        'An array based sort definition must be a $field => "asc" or "desc" list. The value ' .
                        (string)$direction . ' is invalid for field: ' . $field);
                }
            }
            
            return $items;
        }
        
        if (! isset($items)) {
            $items = Arrays::makeFromStringList($query['sort']);
        }
        
        $clean = [];
        foreach ($items as $item) {
            if (str_starts_with($item, '-')) {
                $clean[substr($item, 1)] = 'desc';
            } else {
                $clean[$item] = 'asc';
            }
        }
        
        return $clean;
    }
    
    protected static function parsePage(array $query): array
    {
        if (empty($query['page'])) {
            return [];
        }
        
        if (! is_array($query['page'])) {
            throw new InvalidQueryException('The "page" parameter must be an array');
        }
        
        $clean = $query['page'];
        
        if (isset($clean['number'])) {
            if (! is_numeric($clean['number'])) {
                throw new InvalidQueryException('The "page.number" parameter must be an integer');
            }
            $clean['number'] = max(1, min((int)$clean['number'], QueryDefaults::$maxPage));
        }
        
        if (isset($clean['size'])) {
            if (! is_numeric($clean['size'])) {
                throw new InvalidQueryException('The "page.size" parameter must be an integer');
            }
            $clean['size'] = max(1, min((int)$clean['size'], QueryDefaults::$maxPageSize));
        }
        
        return $clean;
    }
    
    protected static function extractAdditional(array $query, array &$meta): void
    {
        unset(
            $query['page'],
            $query['include'],
            $query['fields'],
            $query['filter'],
            $query['sort'],
        );
        
        $query = array_filter($query);
        $meta['additional'] = $query;
    }
}