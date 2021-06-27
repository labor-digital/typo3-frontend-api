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
 * Last modified: 2021.06.25 at 20:16
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository;


use League\Fractal\Serializer\JsonApiSerializer;

class QueryAwareJsonApiSerializer extends JsonApiSerializer
{
    /**
     * The query parameters to append to each of the auto-generated links
     *
     * @var string
     */
    protected $queryParameters;
    
    public function __construct(string $queryParameters, $baseUrl = null)
    {
        parent::__construct($baseUrl);
        $this->queryParameters = $queryParameters;
    }
    
    /**
     * @inheritDoc
     */
    public function item($resourceKey, array $data)
    {
        $item = parent::item($resourceKey, $data);
        
        if (is_string($item['data']['links']['self'] ?? null)) {
            $this->appendQueryParameters($item['data']['links']['self']);
        }
        
        return $item;
    }
    
    /**
     * @inheritDoc
     */
    public function injectData($data, $includedData)
    {
        $data = parent::injectData($data, $includedData);
        if (is_array($data['data'][0]['relationships'] ?? null)) {
            foreach ($data['data'] as &$_data) {
                $this->processRelationshipLinks($_data);
            }
        } elseif (is_array($data['data']['relationships'] ?? null)) {
            $this->processRelationshipLinks($data['data']);
        }
        
        return $data;
    }
    
    /**
     * Appends the query parameters to the relationship links of the provided data array
     *
     * @param   array  $data
     */
    protected function processRelationshipLinks(array &$data): void
    {
        foreach ($data['relationships'] as &$rel) {
            if (is_string($rel['links']['self'] ?? null)) {
                $this->appendQueryParameters($rel['links']['self']);
            }
            if (is_string($rel['links']['related'] ?? null)) {
                $this->appendQueryParameters($rel['links']['related']);
            }
        }
    }
    
    /**
     * Appends the query parameters to the
     *
     * @param $link
     */
    protected function appendQueryParameters(&$link): void
    {
        if (empty($link) || ! is_string($link)) {
            return;
        }
        
        $link .= $this->queryParameters;
    }
}