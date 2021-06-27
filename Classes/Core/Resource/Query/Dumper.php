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
 * Last modified: 2021.05.06 at 15:33
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Query;


use Neunerlei\Arrays\Arrays;

class Dumper
{
    /**
     * Receives the result of Parser::parse() and flattens the enriched result, so it can be used as request arguments again.
     *
     * @param   array  $parsedQuery
     *
     * @return array
     * @see \LaborDigital\T3fa\Core\Resource\Repository\Query\Parser::parse()
     */
    public static function dump(array $parsedQuery): array
    {
        $result = array_filter([
            'filter' => static::dumpFilter($parsedQuery),
            'fields' => static::dumpFields($parsedQuery),
            'include' => static::dumpInclude($parsedQuery),
            'sort' => static::dumpSorting($parsedQuery),
            'page' => static::dumpPage($parsedQuery),
        ]);
        
        $result = static::dumpAdditional($result, $parsedQuery);
        
        return $result;
    }
    
    protected static function dumpAdditional(array $result, array $query): array
    {
        if (is_array($query['meta']['additional'] ?? null)) {
            return array_merge($result, $query['meta']['additional']);
        }
        
        return $result;
    }
    
    protected static function dumpFilter(array $query): array
    {
        return $query['filter'];
    }
    
    protected static function dumpFields(array $query): array
    {
        $clean = [];
        foreach ($query['fields'] as $resourceType => $fields) {
            $clean[$resourceType] = implode(',', $fields);
        }
        
        return $clean;
    }
    
    protected static function dumpInclude(array $query): string
    {
        if (! empty($query['meta']['includeAll'])) {
            return '*';
        }
        
        if (empty($query['include'])) {
            return '';
        }
        
        $clean = [];
        
        if (is_array($query['include'][''] ?? null)) {
            $clean[] = implode(',', $query['include']['']);
            unset($query['include']['']);
        }
        
        foreach (Arrays::flatten($query['include']) as $path => $lastPart) {
            $parts = explode('.', $path, -1);
            $parts[] = $lastPart;
            $clean[] = implode('.', $parts);
        }
        
        return implode(',', $clean);
    }
    
    protected static function dumpSorting(array $query): string
    {
        $clean = [];
        
        foreach ($query['sort'] as $field => $direction) {
            $clean[] = $direction === 'asc' ? $field : '-' . $field;
        }
        
        return implode(',', $clean);
    }
    
    protected static function dumpPage(array $query): array
    {
        return $query['page'];
    }
}