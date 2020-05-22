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
 * Last modified: 2019.08.19 at 09:53
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Retrieval;


use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouter;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\Arrays\ArrayGeneratorException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\PathUtil\Path;
use TYPO3\CMS\Core\SingletonInterface;
use WoohooLabs\Yang\JsonApi\Hydrator\ClassDocumentHydrator;
use WoohooLabs\Yang\JsonApi\Schema\Document;

class ResourceDataRepository implements SingletonInterface {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouter
	 */
	protected $router;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
	 */
	protected $context;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * ResourceDataRepository constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\TypoContext\TypoContext                 $context
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 * @param \LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouter                   $router
	 */
	public function __construct(TypoContext $context,
								FrontendApiConfigRepository $configRepository,
								ApiRouter $router) {
		
		$this->context = $context;
		$this->configRepository = $configRepository;
		$this->router = $router;
	}
	
	/**
	 * Mostly for internal use. It handles the preconfigured requests that are defined in the
	 * ComponentElementConfigurator class and maps them to the correct repository methods.
	 *
	 * @param array|null $initialRequest The request that was given
	 *
	 * @return ResourceDataResult|null
	 */
	public function findForInitialState(?array $initialRequest): ?ResourceDataResult {
		if (empty($initialRequest)) return NULL;
		if (empty($initialRequest["type"])) return NULL;
		if ($initialRequest["type"] === "query") {
			if (is_numeric($initialRequest["uri"]))
				return $this->findResourceData($initialRequest["resourceType"], (int)$initialRequest["uri"], $initialRequest["query"]);
			else if (empty($initialRequest["uri"]))
				return $this->findResourceCollectionData($initialRequest["resourceType"], $initialRequest["query"]);
			return $this->findAdditionalRouteData($initialRequest["resourceType"], $initialRequest["uri"], $initialRequest["query"]);
		}
		return $this->findUriData($initialRequest["uri"]);
	}
	
	/**
	 * Finds the data for a single entity
	 *
	 * @param string $resourceType The type of entity to resolve
	 * @param int    $id           The uid of the entity to resolve
	 * @param array  $query        A resource query to include related resources or select fields
	 *
	 * @return ResourceDataResult
	 */
	public function findResourceData(string $resourceType, int $id, ?array $query = NULL): ResourceDataResult {
		unset($query["filter"]);
		unset($query["sort"]);
		return $this->handleRequest($resourceType . "/" . $id, $query, $resourceType);
	}
	
	/**
	 * Finds the data for a collection of entities
	 *
	 * @param string $resourceType The type of entity to resolve
	 * @param array  $query        A resource query to narrow the list of entities down
	 *
	 * @return ResourceDataResult|array
	 */
	public function findResourceCollectionData(string $resourceType, array $query = []): ResourceDataResult {
		return $this->handleRequest($resourceType, $query, $resourceType);
	}
	
	/**
	 * Finds the data for "additional routes" that are registered on a certain resource type.
	 *
	 * @param string $resourceType The type of entity to resolve the data for
	 * @param string $uriFragment  The additional uri fragment that should be added after the resource type in the uri
	 * @param array  $query        An optional resource query that depends on the implemented filters on the additional
	 *                             route. Note that additional routes do not natively support pagination!
	 *
	 * @return ResourceDataResult|array
	 */
	public function findAdditionalRouteData(string $resourceType, string $uriFragment, array $query = []): ResourceDataResult {
		return $this->handleRequest($resourceType . "/" . ltrim($uriFragment, "/"), $query, $resourceType);
	}
	
	/**
	 * For the purists out there. Finds the data of some uri.
	 * Note that only uris inside the scope of the frontend API router may be resolved using this method.
	 *
	 * @param string $uri
	 *
	 * @return ResourceDataResult|array
	 */
	public function findUriData(string $uri): ResourceDataResult {
		return $this->handleRequest($uri);
	}
	
	/**
	 * Can be used to re-normalize a json-api response object into a list of entities.
	 * The main entity type will have it's relations resolved into a single data array.
	 *
	 * @param array $data
	 *
	 * @return array
	 * @deprecated Will be removed in v10 use "woohoolabs/yang" instead!
	 */
	public function normalize(array $data): array {
		// Check if the given data is a collection
		if (isset($data["meta"]) && $data["meta"]["pagination"]) $isCollection = TRUE;
		else $isCollection = isset($data["data"]) && isset($data["data"][0]) &&
			is_array($data["data"][0]) && !isset($data["attributes"]);
		$document = Document::fromArray($data);
		$hydrator = new ClassDocumentHydrator();
		if ($isCollection) $obj = $hydrator->hydrateCollection($document);
		else $obj = $hydrator->hydrateSingleResource($document);
		return \GuzzleHttp\json_decode(\GuzzleHttp\json_encode($obj), TRUE);
	}
	
	/**
	 * Is used to submit a request to the router which will then respond (hopefully) with a json object.
	 * This method will parse the json and return it as result data.
	 *
	 * @param string      $uri          The requested uri
	 * @param array|null  $query        The query object which should be added as query string
	 * @param string|null $resourceType Optional resource type which is used for base uri mapping
	 *
	 * @return ResourceDataResult
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	protected function handleRequest(string $uri, ?array $query = [], ?string $resourceType = NULL): ResourceDataResult {
		// Unify the uri
		$uri = "/" . trim(preg_replace("~(\\+|/+)~si", "/", $uri), "/");
		
		// Build the query fragment
		$queryString = "";
		if (!empty($query)) {
			$queryString = $this->formatResourceQuery($query);
			$queryString = (empty($queryString) ? "" : "?" . $queryString);
		}
		
		// Build a resource uri if required
		if (!empty($resourceType)) {
			// Read base url
			$resourceInfo = $this->configRepository->resource()->getResourceConfig($resourceType);
			$baseUri = !empty($resourceInfo) && !empty($resourceInfo->baseUri) ? $resourceInfo->baseUri : "/resources";
			if (stripos($uri, $baseUri . "/") === FALSE) $uri = $baseUri . $uri;
			
			// Read root uri part
			$rootUriPart = "/" . $this->configRepository->routing()->getRootUriPart();
			if (stripos($uri, $rootUriPart . "/") === FALSE) $uri = $rootUriPart . $uri;
		} else {
			// Try to reverse engineer the uri to extract a resource type
			$apiRoot = preg_quote($this->configRepository->routing()->getRootUriPart(), "~");
			$resourceBase = preg_quote($this->configRepository->routing()->getResourceBaseUriPart(), "~");
			$pattern = "~^/$apiRoot/$resourceBase/(.*?)/~";
			preg_match($pattern, $uri, $m);
			if (!empty($m[1]) && $this->configRepository->resource()->isResourceType($m[1])) $resourceType = $m[1];
		}
		
		// Merge uri with query
		$uri .= $queryString;
		
		// Add host and scheme to link
		$pageLink = Path::makeUri(TRUE);
		$link = Path::makeUri($uri);
		if (empty($link->getHost())) $link = $link->withHost($pageLink->getHost());
		if (empty($link->getScheme())) $link = $link->withScheme($pageLink->getScheme());
		
		// Generate the data
		try {
			$response = $this->router->handleLink($link, "internal");
		} catch (NotFoundException $exception) {
			throw new JsonApiException("Failed to retrieve the data for ($link): " . $exception->getMessage());
		}
		
		if ($response->getStatusCode() !== 200) {
			// Show the error when in dev mode
			if ($this->context->Env()->isDev()) {
				echo (string)$response->getBody();
				die;
			}
			throw new JsonApiException("Failed to retrieve the data for ($link): " . $response->getReasonPhrase());
		}
		
		// Convert into an array if possible
		try {
			$data = (string)$response->getBody();
			$data = Arrays::makeFromJson($data);
		} catch (ArrayGeneratorException $e) {
		}
		
		// Build object
		return new LegacyResourceDataResult($data, $resourceType, $link, $query);
		dbge($result->getResourceType(), $result->isSingle(), $result->getData(), $result->getDataNormalized());
		return [
			"data"         => $data,
			"resourceType" => $resourceType,
			"uri"          => (string)$link,
			"query"        => $link->getQuery(),
		];
	}
	
	/**
	 * Is used to convert the given query object into a valid url query string
	 *
	 * @param array  $query  The query array to convert into a string
	 * @param string $prefix Only used internally to handle recursive array resolving.
	 *
	 * @return string
	 */
	protected function formatResourceQuery(array $query, string $prefix = ""): string {
		if (empty($query)) return "";
		$output = [];
		
		$formatString = function ($key, $value): string {
			if (is_array($value)) return $this->formatResourceQuery($value, $key);
			if (empty($value)) return urlencode($key);
			return urlencode($key) . "=" . urlencode($value);
		};
		
		foreach ($query as $k => $v) {
			if (!empty($prefix)) $k = $prefix . "[" . $k . "]";
			if (is_array($v) && !Arrays::isAssociative($v)) $v = implode(",", $v);
			$pair = $formatString($k, $v);
			if (empty($pair)) continue;
			$output[] = $pair;
		}
		
		return implode("&", $output);
	}
}