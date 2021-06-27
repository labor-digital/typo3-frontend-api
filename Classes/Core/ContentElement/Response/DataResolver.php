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
 * Last modified: 2021.06.22 at 18:21
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement\Response;


use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3fa\Core\ContentElement\Adapter\ViewAdapter;
use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformer;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class DataResolver
{
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository
     */
    protected $resourceRepository;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory
     */
    protected $resourceFactory;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformer
     */
    protected $autoTransformer;
    
    public function __construct(
        ResourceRepository $resourceRepository,
        ResourceFactory $resourceFactory,
        AutoTransformer $autoTransformer
    )
    {
        $this->resourceRepository = $resourceRepository;
        $this->resourceFactory = $resourceFactory;
        $this->autoTransformer = $autoTransformer;
    }
    
    /**
     * Generates the "data" based on the entries inside the response and provided view object
     *
     * @param   array          $responseData
     * @param   ViewInterface  $view
     * @param   array          $transformerOptions
     *
     * @return array
     */
    public function generateData(array $responseData, ViewInterface $view, array $transformerOptions): ?array
    {
        // Extract the data from the view
        if ($view instanceof JsonView) {
            $viewData = SerializerUtil::unserializeJson($view->render());
        } else {
            $viewData = ViewAdapter::getVariables($view);
        }
        
        $data = $this->autoTransformer->transform(
            array_merge($viewData, $responseData), $transformerOptions
        );
        
        return empty($data) ? null : $data;
    }
    
    /**
     * Generates the initial state list based on the initial state query provided
     *
     * @param   array|null  $initialStateQuery
     *
     * @return array
     */
    public function generateInitialState(?array $initialStateQuery): ?array
    {
        if ($initialStateQuery === null) {
            return null;
        }
        
        $collection = $this->resourceRepository->getCollection(
            $initialStateQuery['resourceType'],
            $initialStateQuery['query'],
            $initialStateQuery['options']
        );
        
        $transformerOptions = $initialStateQuery['transformerOptions'] ?? [];
        $transformerOptions['jsonApi'] = true;
        
        $state = $collection->asArray($transformerOptions);
        
        // We append the given query so the frontend framework implementation can pick it up
        // and use it as configuration for its resource api
        $query = $initialStateQuery['query'] ?? [];
        $request = $this->resourceFactory->getApiRequest();
        if ($request !== null) {
            $query = array_merge(
                $request->getQueryParams(),
                $query
            );
        }
        $state['meta']['initialState']['query'] = $query;
        $state['meta']['initialState']['resourceType'] = $initialStateQuery['resourceType'];
        
        return $state;
    }
}