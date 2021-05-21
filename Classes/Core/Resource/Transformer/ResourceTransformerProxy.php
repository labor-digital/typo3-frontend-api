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
 * Last modified: 2021.05.20 at 14:27
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer;


use LaborDigital\T3ba\Core\Exception\NotImplementedException;
use League\Fractal\Resource\Primitive;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;

class ResourceTransformerProxy extends TransformerAbstract implements ResourceTransformerInterface
{
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\ResourceTransformerInterface
     */
    protected $concreteTransformer;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\ResourcePostProcessorInterface[]
     */
    protected $postProcessors;
    
    /**
     * The list of "allowed" and "denied" properties in this transformer
     *
     * @var array
     */
    protected $accessInfo;
    
    public function __construct(ResourceTransformerInterface $concreteTransformer, array $postProcessors, array $accessInfo)
    {
        $this->concreteTransformer = $concreteTransformer;
        $this->postProcessors = $postProcessors;
        $this->accessInfo = $accessInfo;
    }
    
    /**
     * @inheritDoc
     */
    public function transform($value): array
    {
        $result = $this->concreteTransformer->transform($value);
        
        foreach ($this->postProcessors as $postProcessor) {
            $result = $postProcessor->process($result, $value);
        }
        
        if (TransformerScope::$accessCheck) {
            if (! empty($this->accessInfo['allowed'])) {
                $result = array_intersect_key($result, array_fill_keys($this->accessInfo['allowed'], true));
            }
            
            if (! empty($this->accessInfo['denied'])) {
                $result = array_diff_key($result, array_fill_keys($this->accessInfo['denied'], true));
            }
        }
        
        return $result;
    }
    
    /**
     * @inheritDoc
     */
    public function getAvailableIncludes(): array
    {
        $includeable = $this->concreteTransformer->getAvailableIncludes();
        
        if (TransformerScope::$accessCheck) {
            if (! empty($this->accessInfo['allowed'])) {
                $includeable = array_intersect($includeable, $this->accessInfo['allowed']);
            }
            
            if (! empty($this->accessInfo['denied'])) {
                $includeable = array_diff($includeable, $this->accessInfo['denied']);
            }
        }
        
        return $includeable;
    }
    
    /**
     * @inheritDoc
     */
    public function getDefaultIncludes(): array
    {
        return $this->concreteTransformer->getDefaultIncludes();
    }
    
    /**
     * @inheritDoc
     */
    public function setAvailableIncludes($availableIncludes)
    {
        throw new NotImplementedException();
    }
    
    /**
     * @inheritDoc
     */
    public function setDefaultIncludes($defaultIncludes)
    {
        throw new NotImplementedException();
    }
    
    /**
     * @inheritDoc
     */
    public function processIncludedResources(Scope $scope, $data)
    {
        if (TransformerScope::$allIncludes) {
            $includedData = [];
            
            foreach ($this->getAvailableIncludes() as $include) {
                if ($resource = $this->callIncludeMethod($scope, $include, $data)) {
                    $childScope = $scope->embedChildScope($include, $resource);
                    
                    if ($childScope->getResource() instanceof Primitive) {
                        $includedData[$include] = $childScope->transformPrimitiveResource();
                    } else {
                        $includedData[$include] = $childScope->toArray();
                    }
                }
            }
            
            return $includedData === [] ? false : $includedData;
        }
        
        return parent::processIncludedResources($scope, $data);
    }
    
    /**
     * @inheritDoc
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->concreteTransformer, $name], $arguments);
    }
    
    
}