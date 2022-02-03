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
 * Last modified: 2021.06.25 at 20:13
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerScope;
use League\Fractal\Manager;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Serializer\SerializerAbstract;
use Neunerlei\Options\Options;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractResourceElement implements NoDiInterface, SelfTransformingInterface
{
    /**
     * The unique resource type this item contains
     *
     * @var string
     */
    protected $resourceType;
    
    /**
     * The raw value that was returned from the resource class
     *
     * @var mixed
     */
    protected $raw;
    
    /**
     * Optional, meta information that were set when the resource object was resolved
     *
     * @var array|null
     */
    protected $meta;
    
    /**
     * The api base url for this resource element
     *
     * @var string
     */
    protected $baseUrl;
    
    /**
     * The query parameters to append to json api links
     *
     * @var string
     */
    protected $linkQueryParams;
    
    /**
     * The list of transformed variants by their unique options key
     *
     * @var array
     */
    protected $transformed = [];
    
    /**
     * The transformer factory, to convert the raw value into it's transformed equivalent.
     *
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory
     */
    protected $transformerFactory;
    
    public function __construct(
        string $resourceType,
        $raw,
        ?array $meta,
        string $baseUrl,
        string $linkQueryParams,
        TransformerFactory $transformerFactory
    )
    {
        $this->resourceType = $resourceType;
        $this->raw = $raw;
        $this->meta = $meta;
        $this->transformerFactory = $transformerFactory;
        $this->linkQueryParams = $linkQueryParams;
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Returns the unique resource type this item contains
     *
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
    
    /**
     * Returns the raw value that was returned from the resource class
     *
     * @return mixed
     */
    public function getRaw()
    {
        return $this->raw;
    }
    
    /**
     * Returns optional, meta information that were set when the resource object was resolved
     *
     * @return array|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }
    
    /**
     * Returns the transformed value as an array
     *
     * @param   array  $options  Options for the transformation process
     *                           - include array|string|true: Defines which sub-resources should be included.
     *                           By default none are included,
     *                           if true is given ALL sub-resources will be included.
     *                           Can be an array of keys or sub.keys.as.paths that match the json:api schema
     *                           - noAccessCheck bool (FALSE): If set to true all property access checks
     *                           are ignored when the value is transformed
     *                           - fields array: Can be a list of fields by their type to create a sparse result
     *                           - jsonApi bool (FALSE): If set to true, the result will serialized for the JSON API
     *
     * @return array
     * @see https://jsonapi.org/format/#fetching-includes
     * @see https://jsonapi.org/format/#fetching-sparse-fieldsets
     */
    public function asArray(array $options = []): array
    {
        // I'm not 100% sold on caching the transformed data here.
        // This saves A LOT of performance when the method gets executed multiple times,
        // but it also bloats up the memory quite a bit :/ I keep that until someone complains about it.
        $storageKey = md5(json_encode($options, JSON_THROW_ON_ERROR));
        
        if (isset($this->transformed[$storageKey])) {
            return $this->transformed[$storageKey];
        }
        
        $pathBackup = TransformerScope::$path;
        $allIncludes = TransformerScope::$allIncludes;
        $accessCheck = TransformerScope::$accessCheck;
        
        $options = Options::make($options, [
            'include' => [
                'type' => ['array', 'true', 'null', 'string'],
                'default' => null,
            ],
            'fields' => [
                'type' => 'array',
                'default' => [],
            ],
            'noAccessCheck' => [
                'type' => 'bool',
                'default' => false,
            ],
            'jsonApi' => [
                'type' => 'bool',
                'default' => false,
            ],
            'addPagination' => [
                'type' => 'bool',
                'default' => false,
            ],
        ]);
        
        try {
            $fractal = GeneralUtility::makeInstance(Manager::class);
            
            TransformerScope::$allIncludes = false;
            TransformerScope::$accessCheck = ! $options['noAccessCheck'];
            
            if ($options['include'] === true || $options['include'] === '*') {
                TransformerScope::$allIncludes = true;
            } elseif (! empty($options['include'])) {
                $fractal->parseIncludes($options['include']);
            }
            
            if (! empty($options['fields'])) {
                $fractal->parseFieldsets($options['fields']);
            }
            
            if ($options['jsonApi']) {
                $serializer = GeneralUtility::makeInstance(
                    QueryAwareJsonApiSerializer::class, $this->linkQueryParams, $this->baseUrl
                );
            } else {
                $serializer = GeneralUtility::makeInstance(DataArraySerializer::class);
            }
            
            if ($serializer instanceof SerializerAbstract) {
                $fractal->setSerializer($serializer);
            }
            
            return $this->transformed[$storageKey]
                = $fractal->createData($this->getFractalElement())->toArray();
            
        } finally {
            TransformerScope::$path = $pathBackup;
            TransformerScope::$allIncludes = $allIncludes;
            TransformerScope::$accessCheck = $accessCheck;
        }
    }
    
    /**
     * Mapping to retrieve the fractal element for the transformation helper
     *
     * @return \League\Fractal\Resource\ResourceAbstract
     */
    abstract protected function getFractalElement(): ResourceAbstract;
}