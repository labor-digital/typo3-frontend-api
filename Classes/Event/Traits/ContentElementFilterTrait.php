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
 * Last modified: 2020.03.20 at 19:52
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event\Traits;


use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;

trait ContentElementFilterTrait {
	/**
	 * True if the frontend action should be called, false if not
	 * @var bool
	 */
	protected $isFrontend;
	
	/**
	 * The used controller instance
	 * @var \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface
	 */
	protected $controller;
	
	/**
	 * The context class that was passed to the controller
	 * @var \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
	 */
	protected $context;
	
	/**
	 * Returns true if the frontend action should be called, false if not
	 * @return bool
	 */
	public function isFrontend(): bool {
		return $this->isFrontend;
	}
	
	/**
	 * Returns the used controller instance
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface
	 */
	public function getController(): ContentElementControllerInterface {
		return $this->controller;
	}
	
	/**
	 * Return the context class that was passed to the controller
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
	 */
	public function getContext(): ContentElementControllerContext {
		return $this->context;
	}
	
}