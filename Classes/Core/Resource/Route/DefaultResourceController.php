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
 * Last modified: 2021.05.31 at 14:47
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Route;


use LaborDigital\T3ba\ExtConfigHandler\Routing\Exceptions\NotFoundException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DefaultResourceController extends AbstractResourceController
{
    /**
     * Handles the request for a single resource
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \LaborDigital\T3ba\ExtConfigHandler\Routing\Exceptions\NotFoundException
     */
    public function singleAction(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $resourceType = $this->validateResourceType($vars);
        
        $resource = $this->resourceRepository->getResource($resourceType, $vars['id']);
        if ($resource === null) {
            throw new NotFoundException('There is no resource of type: "' . $resourceType . '" with id: "' . $vars['id'] . '"');
        }
        
        return $this->getJsonApiResponse(
            $resource->asArray(
                $this->convertRequestToAsArrayOptions($request)
            )
        );
    }
    
    /**
     * Handles the request for a resource collection
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function collectionAction(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $resourceType = $this->validateResourceType($vars);
        
        $collection = $this->resourceRepository->getCollection($resourceType, $request->getQueryParams());
        
        return $this->getJsonApiResponse(
            $collection->asArray(
                $this->convertRequestToAsArrayOptions($request)
            )
        );
    }
    
    /**
     * Handles the relationship action for a single relation field
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \LaborDigital\T3ba\ExtConfigHandler\Routing\Exceptions\NotFoundException
     */
    public function relationshipAction(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $data = $this->resolveRelationData($vars, 'relationship');
        
        return $this->getJsonApiResponse(
            Arrays::getPath($data, ['data', 'relationships', $vars['relationship']], [])
        );
    }
    
    /**
     * Handles the relation resolution on a single related field
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function relationAction(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $data = $this->resolveRelationData($vars, 'related');
        
        return $this->getJsonApiResponse(
            [
                'data' => Arrays::getPath($data, ['included'], []),
                'links' => [
                    'self' => (string)$request->getUri(),
                ],
            ]
        );
    }
    
    /**
     * Resolves the relation data array for the required includes
     *
     * Note: This is not the best option, but currently the only I have to resolve the relations without sacrificing a lot of
     * flexibility in the resource setup itself. If you come across this and have a good alternative, give me a shout please.
     *
     * @param   array   $vars
     * @param   string  $field
     *
     * @return array
     * @throws \LaborDigital\T3ba\ExtConfigHandler\Routing\Exceptions\NotFoundException
     */
    protected function resolveRelationData(array $vars, string $field): array
    {
        $resourceType = $this->validateResourceType($vars);
        
        $resource = $this->resourceRepository->getResource($resourceType, $vars['id']);
        if ($resource === null) {
            throw new NotFoundException('There is no resource of type: "' . $resourceType . '" with id: "' . $vars['id'] . '"');
        }
        
        return $resource->asArray([
            'jsonApi',
            'fields' => [
                $resourceType => ['id', $vars[$field]],
            ],
            'include' => $vars[$field],
        ]);
    }
    
}