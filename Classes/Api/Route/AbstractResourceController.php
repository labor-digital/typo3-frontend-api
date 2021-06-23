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
 * Last modified: 2021.06.23 at 11:16
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Route;


use LaborDigital\T3fa\Core\Resource\Exception\InvalidConfigException;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository;
use LaborDigital\T3fa\Core\Resource\ResourceConfigRepository;
use LaborDigital\T3fa\Core\Routing\Util\ResponseFactoryTrait;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractResourceController implements ResourceControllerInterface
{
    use ResponseFactoryTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository
     */
    protected $resourceRepository;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\ResourceConfigRepository
     */
    protected $configRepository;
    
    public function __construct(ResourceRepository $resourceRepository, ResourceConfigRepository $configRepository)
    {
        $this->resourceRepository = $resourceRepository;
        $this->configRepository = $configRepository;
    }
    
    /**
     * Tries to extract the resource type from the given vars.
     * Fails with an exception if no resource type was provided
     *
     * @param   array  $vars  The vars array provided to the controller action
     *
     * @return string
     * @throws \LaborDigital\T3fa\Core\Resource\Exception\InvalidConfigException
     */
    protected function validateResourceType(array $vars): string
    {
        $resourceType = $this->configRepository->getResourceType($vars['resourceType'] ?? null);
        
        if ($resourceType === null) {
            throw new InvalidConfigException('The route had no provided resource type');
        }
        
        return $resourceType;
    }
    
    /**
     * Extracts possible "asArray" options from the request object and returns a prepared options array
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return array
     * @see \LaborDigital\T3fa\Core\Resource\Repository\AbstractResourceElement::asArray()
     */
    protected function convertRequestToAsArrayOptions(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        $options = ['jsonApi'];
        
        if (is_array($params['fields'] ?? null)) {
            $options['fields'] = $params['fields'];
        }
        
        if (is_string($params['include'] ?? null)) {
            $options['include'] = $params['include'];
        }
        
        return $options;
    }
}