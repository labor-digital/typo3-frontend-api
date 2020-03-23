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
 * Last modified: 2020.03.20 at 21:27
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerProxy;

/**
 * Class TransformerProxyFilterEvent
 *
 * Dispatched when the transformer factory created a new transformer proxy object.
 * Can be used to modify or replace the proxy before it is published
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class TransformerProxyFilterEvent {
	/**
	 * The generated proxy instance
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerProxy
	 */
	protected $proxy;
	
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
	 * TransformerProxyFilterEvent constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerProxy   $proxy
	 * @param string|null                                                              $suggestedResourceType
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory $factory
	 */
	public function __construct(TransformerProxy $proxy, ?string $suggestedResourceType, TransformerFactory $factory) {
		$this->proxy = $proxy;
		$this->suggestedResourceType = $suggestedResourceType;
		$this->factory = $factory;
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
	 * Returns the generated proxy instance
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerProxy
	 */
	public function getProxy(): TransformerProxy {
		return $this->proxy;
	}
	
	/**
	 * Replaces the generated proxy instance
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerProxy $proxy
	 *
	 * @return TransformerProxyFilterEvent
	 */
	public function setProxy(TransformerProxy $proxy): TransformerProxyFilterEvent {
		$this->proxy = $proxy;
		return $this;
	}
	
	
}