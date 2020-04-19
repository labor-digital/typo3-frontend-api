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
 * Last modified: 2019.09.20 at 14:12
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\SiteConfigAwareTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;

class CommonElement implements SelfTransformingInterface {
	use SiteConfigAwareTrait;
	
	/**
	 * The object key for this element
	 * @var string
	 */
	protected $key;
	
	/**
	 * The layout this common element is requested for
	 * @var string
	 */
	protected $layout;
	
	/**
	 * CommonElement constructor.
	 *
	 * @param string $layout The layout this common element is requested for
	 * @param string $key    The object key for this element
	 */
	public function __construct(string $layout, string $key) {
		$this->layout = $layout;
		$this->key = $key;
	}
	
	/**
	 * @inheritDoc
	 */
	public function asArray(): array {
		
		// Check if we got this element
		$siteConfig = $this->getCurrentSiteConfig();
		$layout = $this->layout;
		if (empty($elementList[$layout])) $layout = "default";
		if (!isset($siteConfig->commonElements[$layout]) ||
			!isset($siteConfig->commonElements[$layout][$this->key]))
			throw new JsonApiException("There is no common element with the given key: $this->key");
		
		// Create the instance
		$config = $siteConfig->commonElements[$layout][$this->key];
		switch ($config["type"]) {
			case "contentElement":
				$data = $this->getInstanceOf(ContentElement::class,
					[ContentElement::TYPE_TT_CONTENT, $config["value"]])->asArray();
				break;
			case "ts":
				$data = $this->getInstanceOf(ContentElement::class,
					[ContentElement::TYPE_TYPO_SCRIPT, $config["value"]])->asArray();
				break;
			case "menu":
				$data = $this->getInstanceOf(PageMenu::class, [$this->key, $config["value"]])->asArray();
				break;
			case "custom":
				/** @var \LaborDigital\Typo3FrontendApi\Site\Configuration\CommonCustomElementInterface $handler */
				$handler = $this->getInstanceOf($config["value"]["class"]);
				$data = $handler->asArray($this->key, $config["value"]["data"]);
				break;
			default:
				throw new JsonApiException("Could not render a common element with type: " . $config["type"]);
		}
		
		// Done
		return [
			"id"          => $this->key,
			"layout"      => $this->layout,
			"elementType" => $config["type"],
			"element"     => $data,
		];
	}
	
	/**
	 * Creates a new instance of myself
	 *
	 * @param string $layout
	 * @param string $key
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\CommonElement
	 * @deprecated removed in v10 use the __construct method instead
	 */
	public static function makeInstance(string $layout, string $key): CommonElement {
		return TypoContainer::getInstance()->get(static::class, ["args" => [$layout, $key]]);
	}
}