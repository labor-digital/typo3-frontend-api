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
 * Last modified: 2019.08.29 at 14:43
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\ExtBase;


use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;

class ExtBaseContentElementController implements ContentElementControllerInterface {
	
	/**
	 * Is used to inject the frontend handler closure
	 * @var \Closure|null
	 */
	public static $frontendHandler;
	
	/**
	 * Is used to inject the backend handler closure
	 * @var \Closure|null
	 */
	public static $backendHandler;
	
	/**
	 * @inheritDoc
	 */
	public function handle(ContentElementControllerContext $context) {
		if (!is_callable(static::$frontendHandler)) return "";
		return call_user_func(static::$frontendHandler, $context);
	}
	
	/**
	 * @inheritDoc
	 */
	public function handleBackend(ContentElementControllerContext $context): string {
		if (!is_callable(static::$backendHandler)) return "";
		return call_user_func(static::$backendHandler, $context);
	}
	
}