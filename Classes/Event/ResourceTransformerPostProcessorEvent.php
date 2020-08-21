<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.03.20 at 21:20
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig;

/**
 * Class ResourceTransformerPreProcessorEvent
 *
 * Emitted AFTER a resource transformer converted an ext base object into it's array representation.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ResourceTransformerPostProcessorEvent
{

    /**
     * The array that has been transformed
     *
     * @var array
     */
    protected $result;

    /**
     * The value that should be transformed
     *
     * @var mixed
     */
    protected $value;

    /**
     * The configuration that is generated for the transformation
     *
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
     */
    protected $config;

    /**
     * ResourceTransformerPostProcessorEvent constructor.
     *
     * @param   array                                                                    $result
     * @param                                                                            $value
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig  $config
     */
    public function __construct(array $result, $value, TransformerConfig $config)
    {
        $this->result = $result;
        $this->value  = $value;
        $this->config = $config;
    }

    /**
     * Returns the array that has been transformed
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Updates the array that has been transformed
     *
     * @param   array  $result
     *
     * @return ResourceTransformerPostProcessorEvent
     */
    public function setResult(array $result): ResourceTransformerPostProcessorEvent
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Returns the value that should be transformed
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the configuration that is generated for the transformation
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
     */
    public function getConfig(): TransformerConfig
    {
        return $this->config;
    }
}
