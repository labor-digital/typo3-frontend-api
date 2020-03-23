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
 * Last modified: 2020.03.20 at 20:03
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use LaborDigital\Typo3FrontendApi\Event\Traits\ContentElementFilterTrait;

/**
 * Class ContentElementAfterWrapFilterEvent
 *
 * Dispatched after the content element handler finished with processing the element.
 * It is done after the element data have been converted into a json and wrapped by the configured html tags.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ContentElementAfterWrapFilterEvent {
	use ContentElementFilterTrait;
	
	/**
	 * The compiled html wrapped by the configured wrapper tags
	 * @var string
	 */
	protected $result;
	
	/**
	 * ContentElementAfterControllerFilterEvent constructor.
	 *
	 * @param string                            $result
	 * @param ContentElementControllerInterface $controller
	 * @param ContentElementControllerContext   $context
	 * @param bool                              $isFrontend
	 */
	public function __construct(string $result, ContentElementControllerInterface $controller,
								ContentElementControllerContext $context, bool $isFrontend) {
		$this->result = $result;
		$this->controller = $controller;
		$this->context = $context;
		$this->isFrontend = $isFrontend;
	}
	
	/**
	 * Return the compiled html wrapped by the configured wrapper tags
	 * @return string
	 */
	public function getResult(): string {
		return $this->result;
	}
	
	/**
	 * Used to update the compiled html wrapped by the configured wrapper tags
	 *
	 * @param string $result
	 *
	 * @return ContentElementAfterWrapFilterEvent
	 */
	public function setResult(string $result): ContentElementAfterWrapFilterEvent {
		$this->result = $result;
		return $this;
	}
}