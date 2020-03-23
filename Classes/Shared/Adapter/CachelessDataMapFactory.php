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
 * Last modified: 2019.10.30 at 20:08
 */

namespace LaborDigital\Typo3FrontendApi\Shared\Adapter;


use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;

/**
 * Class CachelessDataMapFactory
 *
 * Adapter for the dataMap factory to create mappings without creating cache entries
 * Noted, its slower but more dynamic.
 *
 * @package LaborDigital\Typo3FrontendApi\Shared\Adapter
 */
class CachelessDataMapFactory extends DataMapFactory {
	
	/**
	 * @inheritDoc
	 */
	public function buildDataMap($className) {
		// Reset the configuration manager cache
		$config = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$this->configurationManager->setConfiguration($config);
		
		// Build the map without the cache
		return $this->buildDataMapInternal($className);
	}
	
}