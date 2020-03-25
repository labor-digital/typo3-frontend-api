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
 * Last modified: 2020.03.25 at 15:10
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


interface PageMenuPostProcessorInterface {
	
	/**
	 * Used to filter a menu after it was generated in the site configuration
	 *
	 * @param string $key      The name/key that was given when the menu was registered
	 * @param array  $menu     The menu configuration to be passed to the frontend
	 * @param array  $options  The options that were given when the menu was registered
	 * @param string $menuType The type of the menu that was generated. One of PageMenu::TYPE_MENU_...
	 *
	 * @return array Must return the modified $menu array to be passed to the frontend
	 */
	public function process(string $key, array $menu, array $options, string $menuType): array;
	
}