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
 * Last modified: 2019.08.26 at 18:22
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Configuration;

use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Strategy\ExtendedJsonStrategy;

class RouteGroupConfig {
	
	/**
	 * The base uri if this route group. Without leading and tailing slashes!
	 * @var string
	 */
	public $groupUri;
	
	/**
	 * The class name of the league route strategy to use.
	 * @var string
	 */
	public $strategy = ExtendedJsonStrategy::class;
	
	/**
	 * The list of routes in this group
	 * @var \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig[]
	 */
	public $routes = [];
	
	/**
	 * The list of middlewares that are registered in this group
	 * @var array
	 */
	public $middlewares = [];
	
	/**
	 * Factory to create a new instance of myself
	 *
	 * @param string $groupUri
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteGroupConfig
	 */
	public static function makeNew(string $groupUri): RouteGroupConfig {
		$i = TypoContainer::getInstance()->get(static::class);
		$i->groupUri = trim($groupUri, "/\\");
		return $i;
	}
}
