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
 * Last modified: 2019.08.26 at 18:48
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Controller;


use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector;
use LaborDigital\Typo3FrontendApi\ApiRouter\Controller\RouteControllerInterface;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use League\Route\Http\Exception\NotFoundException;

class UpController implements RouteControllerInterface {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * SchedulerController constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 */
	public function __construct(FrontendApiConfigRepository $configRepository) {
		$this->configRepository = $configRepository;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function configureRoutes(RouteCollector $routes) {
		$routes->get("up", "upAction", ["useCache" => FALSE]);
	}
	
	/**
	 * Renders a simple OK if the system is up and running.
	 *
	 * @return array
	 * @throws \League\Route\Http\Exception\NotFoundException
	 */
	public function upAction() {
		// Check if the up route is enabled
		if (!$this->configRepository->tool()->get("up.enabled", FALSE))
			throw new NotFoundException();
		
		// Done
		return [
			"status"    => "OK",
			"timestamp" => time(),
		];
	}
}