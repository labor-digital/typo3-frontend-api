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
 * Last modified: 2020.03.20 at 19:56
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use LaborDigital\Typo3FrontendApi\Event\Traits\ContentElementFilterTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement;

/**
 * Class ContentElementSpaEvent
 *
 * Special event that is emitted when the content element handler is running in spa mode.
 * It is used by the ContentElement entity to break the content element rendering by the TSFE to avoid
 * additional overhead we don't need when a content element is populated.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ContentElementSpaEvent
{
    use ContentElementFilterTrait;

    /**
     * The instance of the resource entity
     *
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
     */
    protected $element;

    /**
     * True if the handler should kill itself
     *
     * @var bool
     */
    protected $killHandler = false;

    /**
     * ContentElementPostProcessorEvent constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement               $element
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface  $controller
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext    $context
     * @param   bool                                                                                        $isFrontend
     */
    public function __construct(
        ContentElement $element,
        ContentElementControllerInterface $controller,
        ContentElementControllerContext $context,
        bool $isFrontend
    ) {
        $this->element    = $element;
        $this->controller = $controller;
        $this->context    = $context;
        $this->isFrontend = $isFrontend;
    }

    /**
     * Returns the instance of the resource entity
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
     */
    public function getElement(): ContentElement
    {
        return $this->element;
    }

    /**
     * Returns true if the handler should kill itself
     *
     * @return bool
     */
    public function isKillHandler(): bool
    {
        return $this->killHandler;
    }

    /**
     * Sets the handler to kill itself
     *
     * @param   bool  $killHandler
     *
     * @return ContentElementSpaEvent
     */
    public function setKillHandler(bool $killHandler = true): ContentElementSpaEvent
    {
        $this->killHandler = $killHandler;

        return $this;
    }
}
