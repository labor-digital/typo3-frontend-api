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
 * Last modified: 2021.06.02 at 21:30
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Route;


use League\Route\Http\Exception\NotFoundException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DefaultResourceController extends AbstractResourceController
{
    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function relationshipAction(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $data = $this->resolveRelationData($vars, 'relationship');
        
        return $this->getJsonApiResponse(
            Arrays::getPath($data, ['data', 'relationships', $vars['relationship']], [])
        );
    }
    
    /**
     * @inheritDoc
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