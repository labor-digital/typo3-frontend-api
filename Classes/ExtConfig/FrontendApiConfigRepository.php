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
 * Last modified: 2020.01.16 at 17:04
 */

namespace LaborDigital\Typo3FrontendApi\ExtConfig;

use Closure;
use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RoutingConfigChildRepository;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigChildRepository;
use LaborDigital\Typo3FrontendApi\HybridApp\HybridAppConfigChildRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigChildRepository;
use LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigChildRepository;
use TYPO3\CMS\Core\SingletonInterface;

class FrontendApiConfigRepository implements SingletonInterface {
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
	 */
	protected $container;
	
	/**
	 * The configuration list
	 * @var array|null
	 */
	protected $config;
	
	/**
	 * The closure which is injected by the frontend api option that returns the cached configuration object
	 * @var \Closure
	 */
	protected $configResolver;
	
	/**
	 * The list of repositories that are already instantiated
	 * @var array
	 */
	protected $repositories = [];
	
	/**
	 * FrontendApiConfigRepository constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface $container
	 */
	public function __construct(TypoContainerInterface $container) {
		$this->container = $container;
	}
	
	/**
	 * Contains the routing specific configuration.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RoutingConfigChildRepository
	 */
	public function routing(): RoutingConfigChildRepository {
		return $this->getOrMakeInstance(RoutingConfigChildRepository::class);
	}
	
	/**
	 * Contains the resource specific configuration.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigChildRepository
	 */
	public function resource(): ResourceConfigChildRepository {
		return $this->getOrMakeInstance(ResourceConfigChildRepository::class);
	}
	
	/**
	 * Contains the site specific configuration.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigChildRepository
	 */
	public function site(): SiteConfigChildRepository {
		return $this->getOrMakeInstance(SiteConfigChildRepository::class);
	}
	
	/**
	 * Contains the content element specific configuration.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigChildRepository
	 */
	public function contentElement(): ContentElementConfigChildRepository {
		return $this->getOrMakeInstance(ContentElementConfigChildRepository::class);
	}
	
	/**
	 * Contains the hybrid app specific configuration.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\HybridAppConfigChildRepository
	 */
	public function hybridApp(): HybridAppConfigChildRepository {
		return $this->getOrMakeInstance(HybridAppConfigChildRepository::class);
	}
	
	/**
	 * Contains generic, tool specific configuration.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ExtConfig\ToolConfigChildRepository
	 */
	public function tool(): ToolConfigChildRepository {
		return $this->getOrMakeInstance(ToolConfigChildRepository::class);
	}
	
	/**
	 * Returns a single "namespace" in the configuration object.
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function getConfiguration(string $key) {
		if (!is_array($this->config)) $this->config = call_user_func($this->configResolver);
		if (!isset($this->config[$key])) return NULL;
		if (is_string($this->config[$key])) $this->config[$key] = unserialize($key);
		return $this->config[$key];
	}
	
	/**
	 * Internal helper to inject the configuration resolver in the frontend api option
	 *
	 * @param \Closure $resolver
	 */
	public function __setConfigResolver(Closure $resolver): void {
		$this->configResolver = $resolver;
	}
	
	/**
	 * Internal helper to create a new child repository class
	 *
	 * @param string $repositoryClass
	 *
	 * @return mixed
	 */
	protected function getOrMakeInstance(string $repositoryClass) {
		if (isset($this->repositories[$repositoryClass])) return $this->repositories[$repositoryClass];
		$i = $this->repositories[$repositoryClass] = $this->container->get($repositoryClass);
		if ($i instanceof FrontendApiConfigChildRepositoryInterface) $i->__setParentRepository($this);
		return $i;
	}
}