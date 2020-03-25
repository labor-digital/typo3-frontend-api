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
 * Last modified: 2020.03.20 at 21:00
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


class SiteMenuPostProcessorEvent {
	
	/**
	 * The key that was given for the menu / common element
	 * @var string
	 */
	protected $key;
	
	/**
	 * The parsed menu as array
	 * @var array
	 */
	protected $menu;
	
	/**
	 * The menu type to filter / generate
	 * @var string
	 */
	protected $type;
	
	/**
	 * The options that have been used to generate the menu array
	 * @var array
	 */
	protected $options;
	
	/**
	 * SiteMenuPostProcessorEvent constructor.
	 *
	 * @param string $key
	 * @param array  $menu
	 * @param string $type
	 * @param array  $options
	 */
	public function __construct(string $key, array $menu, string $type, array $options) {
		$this->key = $key;
		$this->menu = $menu;
		$this->type = $type;
		$this->options = $options;
	}
	
	/**
	 * Returns the key that was given for the menu / common element
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}
	
	/**
	 * Returns the menu type to filter / generate
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}
	
	/**
	 * Returns the options that have been used to generate the menu array
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}
	
	/**
	 * Returns the parsed menu as array
	 * @return array
	 */
	public function getMenu(): array {
		return $this->menu;
	}
	
	/**
	 * Updates the parsed menu as array
	 *
	 * @param array $menu
	 *
	 * @return SiteMenuPostProcessorEvent
	 */
	public function setMenu(array $menu): SiteMenuPostProcessorEvent {
		$this->menu = $menu;
		return $this;
	}
	
	
}