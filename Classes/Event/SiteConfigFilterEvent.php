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
 * Last modified: 2020.03.20 at 21:41
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig;

/**
 * Class SiteConfigFilterEvent
 *
 * Can be used to filter the config object after it was generated and before it is cached
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class SiteConfigFilterEvent {
	/**
	 * The Site configuration that was generated
	 * @var \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
	 */
	protected $config;
	
	/**
	 * The class that was used to generate the configuration
	 * @var string
	 */
	protected $configClass;
	
	/**
	 * The ext config context object
	 * @var \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
	 */
	protected $context;
	
	/**
	 * SiteConfigFilterEvent constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig $config
	 * @param string                                                       $configClass
	 * @param \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext      $context
	 */
	public function __construct(SiteConfig $config, string $configClass, ExtConfigContext $context) {
		$this->config = $config;
		$this->configClass = $configClass;
		$this->context = $context;
	}
	
	/**
	 * Returns the Site configuration that was generated
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
	 */
	public function getConfig(): SiteConfig {
		return $this->config;
	}
	
	/**
	 * Returns the class that was used to generate the configuration
	 * @return string
	 */
	public function getConfigClass(): string {
		return $this->configClass;
	}
	
	/**
	 * Returns the ext config context object
	 * @return \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
	 */
	public function getContext(): ExtConfigContext {
		return $this->context;
	}
}