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
 * Last modified: 2019.08.19 at 12:34
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Controller;


use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig;
use League\Route\Http\Exception\BadRequestException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;

class JsonApiResourceQuery
{

    /**
     * The parsed filter fields in the query
     *
     * @var array
     */
    protected $filters = [];

    /**
     * The parsed sorting fields and their order direction in the query
     *
     * @var array
     */
    protected $sorting = [];

    /**
     * The list of included fields in the request
     * May contain "*" for all includes
     *
     * @var array
     */
    protected $included = [];

    /**
     * The type of pagination we should use "pages"/"cursor"
     *
     * @var string
     */
    protected $paginationType = "pages";

    /**
     * Either the number of the given page or null if there was none given
     *
     * @var int|null
     */
    protected $page;

    /**
     * Either the size of the page or null if there was none given
     *
     * @var int|null
     */
    protected $pageSize;

    /**
     * Additional query parameters that were given
     *
     * @var array
     */
    protected $remainingQuery = [];

    /**
     * The raw query parameters
     *
     * @var array
     */
    protected $raw;

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array
     */
    public function getSorting(): array
    {
        return $this->sorting;
    }

    /**
     * @return array
     */
    public function getIncluded(): array
    {
        return $this->included;
    }

    /**
     * @return string
     */
    public function getPaginationType(): string
    {
        return $this->paginationType;
    }

    /**
     * @return int|null
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * @return int|null
     */
    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    /**
     * Returns additional query parts that don't have a direct method linked to them
     *
     * @param   string|array  $path
     * @param   null          $default
     *
     * @return array|mixed|null
     */
    public function get($path, $default = null)
    {
        return Arrays::getPath($this->remainingQuery, $path, $default);
    }

    /**
     * Returns the raw query parameters as array
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }

    /**
     * Returns the remaining parts if you don't want to select a specific part using "get()"
     *
     * @return array
     */
    public function getRemainingParts(): array
    {
        return $this->remainingQuery;
    }

    /**
     * Factory to create a new query object based on the given request
     *
     * @param   \Psr\Http\Message\ServerRequestInterface                             $request
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfig  $config
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Controller\JsonApiResourceQuery
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    public static function makeNewInstance(ServerRequestInterface $request, ResourceConfig $config): JsonApiResourceQuery
    {
        $i           = new static();
        $queryParams = $request->getQueryParams();
        $i->raw      = $queryParams;

        // Get filters
        if (isset($queryParams["filter"]) && ! empty($queryParams["filter"])) {
            if (! is_array($queryParams["filter"])) {
                throw new BadRequestException("The filter parameter is invalid!");
            }
            $i->filters = $queryParams["filter"];
        }

        // Get the sorting
        if (isset($queryParams["sort"]) && ! empty($queryParams["sort"])) {
            $sortList = Arrays::makeFromStringList($queryParams["sort"]);
            $sorting  = [];
            foreach ($sortList as $field) {
                // Get direction from field name
                $direction = substr($field, 0, 1) === "-" ? "desc" : "asc";
                if ($direction === "desc") {
                    $field = substr($field, 1);
                }
                $sorting[$field] = $direction;
            }
            $i->sorting = $sorting;
        }

        // Get included
        if (isset($queryParams["include"])) {
            $i->included = Arrays::makeFromStringList($queryParams["include"]);
        }

        // Get pagination
        if (isset($queryParams["page"])) {
            if (! is_array($queryParams["page"])) {
                throw new BadRequestException("The page parameter is invalid!");
            }
            if (isset($queryParams["page"]["number"])) {
                $i->page = max(1, (int)$queryParams["page"]["number"]);
            }
            if (isset($queryParams["page"]["size"])) {
                $i->pageSize = min(1000, max(1, (int)$queryParams["page"]["size"]));
            } else {
                $i->pageSize = $config->pageSize;
            }
        }

        // Prepare the remaining query elements
        unset($queryParams["filter"], $queryParams["sort"], $queryParams["include"]);
        if (is_array($queryParams["page"])) {
            unset($queryParams["page"]["number"]);
            unset($queryParams["page"]["size"]);
        }
        $i->remainingQuery = $queryParams;

        // Done
        return $i;
    }
}
