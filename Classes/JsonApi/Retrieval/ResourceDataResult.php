<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.05.22 at 18:29
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Retrieval;


use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use Psr\Http\Message\UriInterface;
use WoohooLabs\Yang\JsonApi\Hydrator\ClassDocumentHydrator;
use WoohooLabs\Yang\JsonApi\Schema\Document;

class ResourceDataResult implements SelfTransformingInterface
{
    
    /**
     * The data that was responded by the resource controller
     *
     * @var array|array[]
     */
    protected $data;
    
    /**
     * The resource type contained in this result
     *
     * @var string|null
     */
    protected $resourceType;
    
    /**
     * The URI that was requested to resolve the resources
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;
    
    /**
     * The given query array or null
     *
     * @var array|null
     */
    protected $query;
    
    /**
     * The given query as a URL formatted string
     *
     * @var string
     */
    protected $queryString;
    
    /**
     * ResourceDataResult constructor.
     *
     * @param   array                           $data
     * @param   string|null                     $resourceType
     * @param   \Psr\Http\Message\UriInterface  $uri
     * @param   array|null                      $query
     */
    public function __construct($data, ?string $resourceType, UriInterface $uri, ?array $query)
    {
        $this->data         = is_array($data) ? $data : [];
        $this->resourceType = $resourceType;
        $this->uri          = $uri;
        $this->query        = $query;
    }
    
    /**
     * Returns the data that was responded by the resource controller
     *
     * @param   bool  $raw  If set to true the raw data is returned (including meta and links if they are present)
     *
     * @return array[]
     */
    public function getData(bool $raw = false): array
    {
        return ! $raw && isset($this->data["data"]) ? $this->data["data"] : $this->data;
    }
    
    /**
     * Returns the data that was responded by the resource controller by normalized as usable objects
     *
     * @return array
     */
    public function getDataNormalized(): array
    {
        $document = Document::fromArray($this->data);
        $hydrator = new ClassDocumentHydrator();
        if ($this->isCollection()) {
            $obj = $hydrator->hydrateCollection($document);
        } else {
            $obj = $hydrator->hydrateSingleResource($document);
        }
        
        return \GuzzleHttp\json_decode(\GuzzleHttp\json_encode($obj), true);
    }
    
    /**
     * Returns additional metadata that has been returned by the resource controller
     *
     * @return array
     */
    public function getMeta(): array
    {
        return isset($this->data["meta"]) ? $this->data["meta"] : [];
    }
    
    /**
     * Returns the list of links that have been returned by the resource controller
     *
     * @return array
     */
    public function getLinks(): array
    {
        return isset($this->data["links"]) ? $this->data["links"] : [];
    }
    
    /**
     * Returns true if this result contains a single item
     *
     * @return bool
     */
    public function isSingle(): bool
    {
        return ! $this->isCollection();
    }
    
    /**
     * Returns true if this result contains a collection of items
     *
     * @return bool
     */
    public function isCollection(): bool
    {
        if (isset($this->data["meta"]) && $this->data["meta"]["pagination"]) {
            return true;
        }
        
        return isset($this->data["data"]) && isset($this->data["data"][0])
               && is_array($this->data["data"][0])
               && ! isset($this->data["attributes"]);
    }
    
    /**
     *
     * @return string|null
     */
    public function getResourceType(): ?string
    {
        if ($this->isSingle()) {
            if (isset($this->data["data"]) && isset($this->data["data"]["type"])) {
                return $this->data["data"]["type"];
            }
        } elseif (isset($this->data["data"]) && isset($this->data["data"][0]) && isset($this->data["data"][0])
                  && isset($this->data["data"][0]["type"])) {
            return $this->data["data"][0]["type"];
        }
        
        return $this->resourceType;
    }
    
    /**
     * @return \Psr\Http\Message\UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }
    
    /**
     * @return array|null
     */
    public function getQuery(): ?array
    {
        return $this->query;
    }
    
    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }
    
    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return in_array($offset, ["data", "resourceType", "uri", "query"]);
    }
    
    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return [
            "data"         => $this->data,
            "resourceType" => $this->getResourceType(),
            "uri"          => (string)$this->getUri(),
            "query"        => $this->getUri()->getQuery(),
        ];
    }
    
}
