<?php
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
 * Last modified: 2019.08.20 at 11:56
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use Closure;
use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3BetterApi\LazyLoading\LazyLoadingTrait;
use LaborDigital\Typo3FrontendApi\Event\TransformerConfigFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\TransformerInstanceFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\TransformerProxyFilterEvent;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\Arrays;
use Neunerlei\EventBus\EventBusInterface;
use TYPO3\CMS\Core\SingletonInterface;

class TransformerFactory implements SingletonInterface {
	use LazyLoadingTrait;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfigGenerator
	 */
	protected $transformerConfigGenerator;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
	 */
	protected $container;
	
	/**
	 * @var \Neunerlei\EventBus\EventBusInterface
	 */
	protected $eventBus;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	
	/**
	 * TransformerFactory constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface                    $container
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfigGenerator $transformerConfigGenerator
	 * @param \Neunerlei\EventBus\EventBusInterface                                            $eventBus
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository             $configRepository
	 */
	public function __construct(TypoContainerInterface $container, TransformerConfigGenerator $transformerConfigGenerator,
								EventBusInterface $eventBus, FrontendApiConfigRepository $configRepository) {
		$this->container = $container;
		$this->transformerConfigGenerator = $transformerConfigGenerator;
		$this->eventBus = $eventBus;
		$this->configRepository = $configRepository;
	}
	
	/**
	 * Generates a new instance of a transformer proxy.
	 * We proxy the request to all transformers to make sure the configured, mapped transformer class is
	 * created for the given value.
	 *
	 * @param string|null $suggestedResourceType Holds the suggested resource type we should generate the configuration
	 *                                           for. Is only used if the resource type would otherwise be resolved into
	 *                                           an "auto"... something
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerProxy
	 */
	public function getTransformer(?string $suggestedResourceType = NULL): TransformerProxy {
		
		// Create the instance
		$instanceCreator = $this->makeInstanceCreator($suggestedResourceType);
		$proxy = $this->container->get(TransformerProxy::class, ["args" => [$instanceCreator]]);
		
		// Allow filtering
		$this->eventBus->dispatch(($e = new TransformerProxyFilterEvent($proxy, $suggestedResourceType, $this)));
		return $e->getProxy();
	}
	
	/**
	 * Returns the transformer configuration for the given value
	 *
	 * @param mixed       $value                 The value to find the correct configuration for
	 * @param string|null $suggestedResourceType Holds the suggested resource type we should generate the configuration
	 *                                           for. Is only used if the resource type would otherwise be resolved into
	 *                                           an "auto"... something
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfig*
	 */
	public function getConfigFor($value, ?string $suggestedResourceType = NULL): TransformerConfig {
		// Find the relevant object
		$value = $this->lazyLoading->getRealValue($value);
		if (is_iterable($value)) {
			// Make sure to convert associative arrays not as lists
			if (!is_array($value) || is_array($value) && Arrays::isSequential($value)) {
				foreach ($value as $_v) {
					if (!is_object($_v)) break;
					$value = $_v;
					break;
				}
			}
		}
		
		// Resolve resource type and resource config
		$resourceType = $this->configRepository->resource()->getResourceTypeByValue($value, FALSE);
		if (empty($resourceType)) $resourceType = $suggestedResourceType;
		if (empty($resourceType)) $resourceType = $this->configRepository->resource()->getResourceTypeByValue($value);
		$resourceConfig = $this->configRepository->resource()->getResourceConfig($resourceType);
		
		// Generate the config object
		$config = $this->transformerConfigGenerator->makeTransformerConfigFor($value, $resourceType, $resourceConfig);
		
		// Allow filtering
		$this->eventBus->dispatch(($e = new TransformerConfigFilterEvent($config, $value, $suggestedResourceType, $this)));
		return $e->getConfig();
	}
	
	/**
	 * Creates the closure to build the real transformer instance inside the transformer proxy
	 *
	 * @param string|null $suggestedResourceType
	 *
	 * @return \Closure
	 */
	protected function makeInstanceCreator(?string $suggestedResourceType): Closure {
		return function ($value) use ($suggestedResourceType): AbstractResourceTransformer {
			// Load the configuration
			$config = $this->getConfigFor($value, $suggestedResourceType);
			
			// Create transformer instance
			/** @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer $instance */
			$instance = $this->container->get($config->transformerClass);
			$instance->setFactory($this);
			
			// Update the config
			$instance->setTransformerConfig($config);
			
			// Allow filtering
			$this->eventBus->dispatch(($e = new TransformerInstanceFilterEvent($config, $instance, $config, $this)));
			return $e->getTransformer();
		};
	}
}