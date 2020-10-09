<?php
declare(strict_types=1);
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
 * Last modified: 2019.08.11 at 12:58
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use LaborDigital\Typo3FrontendApi\Cache\Scope\CacheTagAwareInterface;
use LaborDigital\Typo3FrontendApi\Event\ResourceTransformerPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\ResourceTransformerPreProcessorEvent;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\TransformerAbstract;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

abstract class AbstractResourceTransformer extends TransformerAbstract
{
    use TransformerTrait;

    /**
     * Receives the value and should convert it into an array
     *
     * It behaves exactly as Fractal's "transform" method would but we fill that with additional overhead,
     * so we had to adjust the naming a bit. See fractal's transformer documentation for additional info
     *
     * @param   mixed  $value
     *
     * @return array
     * @see https://fractal.thephpleague.com/transformers/
     */
    abstract protected function transformValue($value): array;

    /**
     * Helper to create the "include" collection when building your own transformer
     *
     * @param $value
     *
     * @return \League\Fractal\Resource\Collection
     */
    protected function autoIncludeCollection($value): Collection
    {
        $factory = $this->TransformerFactory();

        return $this->collection($value,
            $factory->getTransformer()->getConcreteTransformer($value),
            $factory->getConfigFor($value)->resourceType);
    }

    /**
     * Helper to create the "include" item when building your own transformer
     *
     * @param $value
     *
     * @return ResourceAbstract
     */
    protected function autoIncludeItem($value): ResourceAbstract
    {
        $factory = $this->TransformerFactory();

        if (empty($value)) {
            return $this->null();
        }

        return $this->item($value,
            $factory->getTransformer()->getConcreteTransformer($value),
            $factory->getConfigFor($value)->resourceType);
    }

    /**
     * This method hooks into the processor of fractal and adds our additional,
     * generic overhead to it we want to apply to all called transformers
     *
     * @param $value
     *
     * @return array
     */
    final public function transform($value): array
    {
        // Enable additional processing
        $context = $this->FrontendApiContext();
        $context->EventBus()->dispatch(($e = new ResourceTransformerPreProcessorEvent($value, $this->config)));
        $this->config = $e->getConfig();
        $value        = $e->getValue();

        // Transform the item
        $result = $this->transformValue($value);
        $result = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        // Check if there are registered post processors
        if (! empty($this->config->postProcessors)) {
            foreach ($this->config->postProcessors as $postProcessor) {
                $result = $postProcessor(
                    $result,
                    $value,
                    $this->config->resourceType,
                    $this->config->resourceConfig
                );
            }
        }

        // Apply check for allowed and denied properties
        if (! empty($this->config->resourceConfig)) {
            $hasAllowed = ! empty($this->config->resourceConfig->allowedProperties);
            $hasDenied  = ! empty($this->config->resourceConfig->deniedProperties);
            if ($hasAllowed || $hasDenied) {
                $filtered = [];
                foreach ($result as $k => $v) {
                    // Always include the id property
                    if ($k !== 'id') {
                        // Check if the property is allowed
                        if ($hasAllowed && ! in_array($k, $this->config->resourceConfig->allowedProperties, true)) {
                            continue;
                        }

                        // Check if the property is denied
                        if ($hasDenied && in_array($k, $this->config->resourceConfig->deniedProperties, true)) {
                            continue;
                        }
                    }

                    // Add to filtered list
                    $filtered[$k] = $v;
                }
                $result = $filtered;
            }
        }

        // Enable additional processing
        $result = $context->EventBus()->dispatch(
            new ResourceTransformerPostProcessorEvent($result, $value, $this->config)
        )->getResult();

        // Announce cache tag
        if ($value instanceof AbstractEntity || $value instanceof CacheTagAwareInterface) {
            $context->CacheService()
                    ->announceTag($value);
        }

        return $result;
    }
}
