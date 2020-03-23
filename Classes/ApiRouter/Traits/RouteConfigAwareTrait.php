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
 * Last modified: 2019.08.26 at 21:23
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Traits;


use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use League\Route\Route;

trait RouteConfigAwareTrait {
	
	/**
	 * @var FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * Injects the config repository to resolve the route information with
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 */
	public function injectConfigRepository(FrontendApiConfigRepository $configRepository) {
		$this->configRepository = $configRepository;
	}
	
	/**
	 * Finds the route configuration object for a given router-route.
	 *
	 * @param \League\Route\Route $route
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
	 */
	public function getRouteConfig(Route $route): RouteConfig {
		return $this->configRepository->routing()->getRouteConfig($route);
	}
}