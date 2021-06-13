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
 * Last modified: 2021.06.09 at 11:21
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api;


use InvalidArgumentException;
use LaborDigital\T3fa\Core\Resource\Transformer\ResourcePostProcessorInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\ResourceTransformerInterface;

trait TransformerRegistrationTrait
{
    /**
     * Internal helper to add a given transformer class for one or multiple target classes/interfaces.
     *
     * @param   array         $list                          The list to add the transformer definition to
     * @param   string|array  $targetClassOrInterfaceOrList  Either a single target type, or a list of target types
     * @param   string        $transformerClassName          The transformer class to register for the given target
     * @param   string|null   $requiredInterface             An optional interface name the registered transformer has to implement
     *
     * @return array
     */
    protected function addTransformerToList(array $list, $targetClassOrInterfaceOrList, string $transformerClassName, ?string $requiredInterface = null): array
    {
        if (is_array($targetClassOrInterfaceOrList)) {
            foreach ($targetClassOrInterfaceOrList as $target) {
                $list = $this->addTransformerToList($list, $target, $transformerClassName, $requiredInterface);
            }
            
            return $list;
        }
        
        // We deliberately don't check if the class or interface exists,
        // so even non existent classes of (potentially not installed) extensions can be mapped
        if (! is_string($targetClassOrInterfaceOrList)) {
            throw new InvalidArgumentException('Could not register transformer: "' . $transformerClassName . '" because one or multiple target definitions are invalid');
        }
        
        $requiredInterface = $requiredInterface ?? ResourceTransformerInterface::class;
        if (! class_exists($transformerClassName) || ! in_array($requiredInterface, class_implements($transformerClassName), true)) {
            throw new InvalidArgumentException('The given transformer: "' . $transformerClassName . '" either does not exist, or does not extend the required interface: "' . $requiredInterface . '"');
        }
        
        $list[$targetClassOrInterfaceOrList] = $transformerClassName;
        
        return $list;
    }
    
    /**
     * Internal helper to add a given post processor class for one or multiple target classes/interfaces.
     *
     * @param   array         $list                          The list to add the post processor to
     * @param   string|array  $targetClassOrInterfaceOrList  Either a single target type, or a list of target types
     * @param   string        $postProcessorClass            The post processor class to register
     *
     * @return array
     */
    protected function addPostProcessorToList(array $list, $targetClassOrInterfaceOrList, string $postProcessorClass): array
    {
        if (is_array($targetClassOrInterfaceOrList)) {
            foreach ($targetClassOrInterfaceOrList as $target) {
                $list = $this->addPostProcessorToList($list, $target, $postProcessorClass);
            }
            
            return $list;
        }
        
        // We deliberately don't check if the class or interface exists,
        // so even non existent classes of (potentially not installed) extensions can be mapped
        if (! is_string($targetClassOrInterfaceOrList)) {
            throw new InvalidArgumentException('Could not register post processor: "' . $postProcessorClass . '" because one or multiple target definitions are invalid');
        }
        
        if (! class_exists($postProcessorClass) || ! in_array(ResourcePostProcessorInterface::class, class_implements($postProcessorClass), true)) {
            throw new InvalidArgumentException('The given post processor: "' . $postProcessorClass . '" either does not exist, or does not extend the required interface: "' . ResourcePostProcessorInterface::class . '"');
        }
        
        $list[$targetClassOrInterfaceOrList] = array_unique(
            array_merge(
                $list[$targetClassOrInterfaceOrList] ?? [],
                [$postProcessorClass]
            )
        );
        
        return $list;
    }
}