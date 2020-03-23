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
 * Last modified: 2020.03.20 at 19:53
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use LaborDigital\Typo3FrontendApi\Event\Traits\ContentElementFilterTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement;

/**
 * Class ContentElementPostProcessorEvent
 *
 * Dispatched after the content element handler performed the post processing on the
 * result data that was returned by the controller. The result has now been converted
 * into a instance of the "ContentElement" entity
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ContentElementPostProcessorEvent {
	use ContentElementFilterTrait;
	
	/**
	 * The instance of the resource entity
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
	 */
	protected $element;
	
	/**
	 * ContentElementPostProcessorEvent constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement              $element
	 * @param \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface $controller
	 * @param \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext   $context
	 * @param bool                                                                                       $isFrontend
	 */
	public function __construct(ContentElement $element, ContentElementControllerInterface $controller,
								ContentElementControllerContext $context, bool $isFrontend) {
		$this->element = $element;
		$this->controller = $controller;
		$this->context = $context;
		$this->isFrontend = $isFrontend;
	}
	
	/**
	 * Returns the instance of the resource entity
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
	 */
	public function getElement(): ContentElement {
		return $this->element;
	}
	
	/**
	 * Updates the instance of the resource entity
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement $element
	 *
	 * @return ContentElementPostProcessorEvent
	 */
	public function setElement(ContentElement $element): ContentElementPostProcessorEvent {
		$this->element = $element;
		return $this;
	}
}