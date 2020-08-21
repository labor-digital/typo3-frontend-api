<?php
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.08.12 at 22:42
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Controller;


use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig;
use Psr\Http\Message\ServerRequestInterface;

class ResourceControllerContext
{

    /**
     * Holds the server request instance
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * The request parameters
     *
     * @var array
     */
    protected $params;

    /**
     * Holds the resource type of this request
     *
     * @var string|null
     */
    protected $resourceType;

    /**
     * Holds the resource config for this request
     *
     * @var ResourceConfig
     */
    protected $resourceConfig;

    /**
     * Additional metadata for the response object
     *
     * @var array|null
     */
    protected $meta;

    /**
     * The instance of the parsed query object
     *
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Controller\JsonApiResourceQuery
     */
    protected $query;

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return ResourceControllerContext
     */
    public function setRequest(ServerRequestInterface $request): ResourceControllerContext
    {
        $this->query   = JsonApiResourceQuery::makeNewInstance($request);
        $this->request = $request;

        return $this;
    }

    /**
     * Returns the instance of the resource query for this request
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Controller\JsonApiResourceQuery
     */
    public function getQuery(): JsonApiResourceQuery
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param   array  $params
     *
     * @return ResourceControllerContext
     */
    public function setParams(array $params): ResourceControllerContext
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    /**
     * @param   string|null  $resourceType
     *
     * @return ResourceControllerContext
     */
    public function setResourceType(?string $resourceType): ResourceControllerContext
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    /**
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig
     */
    public function getResourceConfig(): ResourceConfig
    {
        return $this->resourceConfig;
    }

    /**
     * @param   ResourceConfig  $resourceConfig
     *
     * @return ResourceControllerContext
     */
    public function setResourceConfig(ResourceConfig $resourceConfig): ResourceControllerContext
    {
        $this->resourceConfig = $resourceConfig;

        return $this;
    }

    /**
     * Returns additional metadata that is set for the response
     *
     * @return array|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * Can be used to add additional metadata to the response
     *
     * @param   array|null  $meta
     *
     * @return $this
     */
    public function setMeta(?array $meta): ResourceControllerContext
    {
        $this->meta = $meta;

        return $this;
    }
}
