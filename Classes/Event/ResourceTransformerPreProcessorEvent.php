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
 * Last modified: 2020.03.20 at 21:18
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig;

/**
 * Class ResourceTransformerPreProcessorEvent
 *
 * Emitted before a resource transformer converts an ext base object into it's array representation.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ResourceTransformerPreProcessorEvent {
	
	/**
	 * The value that should be transformed
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * The configuration that is generated for the transformation
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	protected $config;
	
	/**
	 * ResourceTransformerPreProcessorEvent constructor.
	 *
	 * @param                                                                         $value
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig $config
	 */
	public function __construct($value, TransformerConfig $config) {
		$this->value = $value;
		$this->config = $config;
	}
	
	/**
	 * Returns the value that should be transformed
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Update the value that should be transformed
	 *
	 * @param mixed $value
	 *
	 * @return ResourceTransformerPreProcessorEvent
	 */
	public function setValue($value): ResourceTransformerPreProcessorEvent {
		$this->value = $value;
		return $this;
	}
	
	/**
	 * Returns the configuration that is generated for the transformation
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	public function getConfig(): TransformerConfig {
		return $this->config;
	}
	
	/**
	 * Updates the configuration that is generated for the transformation
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig $config
	 *
	 * @return ResourceTransformerPreProcessorEvent
	 */
	public function setConfig(TransformerConfig $config): ResourceTransformerPreProcessorEvent {
		$this->config = $config;
		return $this;
	}
}