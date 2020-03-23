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
 * Last modified: 2020.03.20 at 21:35
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;

/**
 * Class TransformerInstanceFilterEvent
 *
 * Dispatched when a transformer proxy generated a new instance of a concrete transformer.
 * Can be used to update the instance before it gets used.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class TransformerInstanceFilterEvent {
	/**
	 * The configuration that was generated for the transformer
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	protected $config;
	
	/**
	 * The transformer instance that was created
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer
	 */
	protected $transformer;
	
	/**
	 * The value that should be transformed
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * The factory instance that created the transformer instance
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
	 */
	protected $factory;
	
	/**
	 * TransformerInstanceFilterEvent constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig           $config
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer $transformer
	 * @param                                                                                   $value
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory          $factory
	 */
	public function __construct(TransformerConfig $config, AbstractResourceTransformer $transformer,
								$value, TransformerFactory $factory) {
		$this->config = $config;
		$this->transformer = $transformer;
		$this->value = $value;
		$this->factory = $factory;
	}
	
	/**
	 * Returns the configuration that was generated for the transformer
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig
	 */
	public function getConfig(): TransformerConfig {
		return $this->config;
	}
	
	/**
	 * Returns the value that should be transformed
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Returns the factory instance that created the transformer instance
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
	 */
	public function getFactory(): TransformerFactory {
		return $this->factory;
	}
	
	/**
	 * Returns the transformer instance that was created
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer
	 */
	public function getTransformer(): AbstractResourceTransformer {
		return $this->transformer;
	}
	
	/**
	 * Replaces the transformer instance that was created
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer $transformer
	 *
	 * @return TransformerInstanceFilterEvent
	 */
	public function setTransformer(AbstractResourceTransformer $transformer): TransformerInstanceFilterEvent {
		$this->transformer = $transformer;
		return $this;
	}
}