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
 * Last modified: 2020.03.20 at 21:31
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;

/**
 * Class TransformerConfigFilterEvent
 *
 * Dispatched when a transformer proxy requests the configuration for a certain resource type
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class TransformerConfigFilterEvent {
	
	/**
	 * The configuration that was generated for the value
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	protected $config;
	
	/**
	 * The value the configuration was generated for
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * The suggested resource type we should generate the configuration
	 * for. Is only used if the resource type would otherwise be resolved into
	 * an "auto"... something
	 * @var string|null
	 */
	protected $suggestedResourceType;
	
	/**
	 * The factory instance that was used to generate the proxy
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
	 */
	protected $factory;
	
	/**
	 * TransformerConfigFilterEvent constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig  $config
	 * @param mixed                                                                    $value
	 * @param string|null                                                              $suggestedResourceType
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory $factory
	 */
	public function __construct(TransformerConfig $config, $value, ?string $suggestedResourceType, TransformerFactory $factory) {
		$this->config = $config;
		$this->value = $value;
		$this->suggestedResourceType = $suggestedResourceType;
		$this->factory = $factory;
	}
	
	/**
	 * Returns the value the configuration was generated for
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Returns the suggested resource type we should generate the configuration for
	 * @return string
	 */
	public function getSuggestedResourceType(): ?string {
		return $this->suggestedResourceType;
	}
	
	/**
	 * Return the factory instance that was used to generate the proxy
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
	 */
	public function getFactory(): TransformerFactory {
		return $this->factory;
	}
	
	/**
	 * Returns the configuration that was generated for the value
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	public function getConfig(): TransformerConfig {
		return $this->config;
	}
	
	/**
	 * Replaces the configuration that was generated for the value
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig $config
	 *
	 * @return TransformerConfigFilterEvent
	 */
	public function setConfig(TransformerConfig $config): TransformerConfigFilterEvent {
		$this->config = $config;
		return $this;
	}
	
	
}