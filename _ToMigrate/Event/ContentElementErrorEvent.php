<?php
/*
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
 * Last modified: 2020.12.01 at 16:29
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use Throwable;

class ContentElementErrorEvent
{

    /**
     * The error thrown in the content element
     *
     * @var \Throwable
     */
    protected $throwable;

    /**
     * True if the element is rendered for the frontend
     *
     * @var bool
     */
    protected $isFrontend;

    /**
     * @var \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface
     */
    protected $controller;

    /**
     * @var \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
     */
    protected $context;

    /**
     * If an event handler was able to handle the error by rendering a custom result,
     * the result will be stored here
     *
     * @var mixed
     */
    protected $result;

    public function __construct(
        Throwable $throwable,
        bool $isFrontend,
        ContentElementControllerInterface $controller,
        ContentElementControllerContext $context
    ) {
        $this->throwable  = $throwable;
        $this->isFrontend = $isFrontend;
        $this->controller = $controller;
        $this->context    = $context;
    }

    /**
     * Returns the error thrown in the content element
     *
     * @return \Throwable
     */
    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }

    /**
     * Returns true if the element is rendered for the frontend
     *
     * @return bool
     */
    public function isFrontend(): bool
    {
        return $this->isFrontend;
    }

    /**
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface
     */
    public function getController(): ContentElementControllerInterface
    {
        return $this->controller;
    }

    /**
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
     */
    public function getContext(): ContentElementControllerContext
    {
        return $this->context;
    }

    /**
     * Returns true if an event handler set a result -> meaning we have something to show now
     *
     * @return bool
     */
    public function isHandled(): bool
    {
        return $this->result !== null;
    }

    /**
     * Returns either the provided custom result for the failed element or null, if the error was not handled yet
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Allows you to provide a custom result for the failed content element.
     *
     * @param   mixed  $result
     *
     * @return ContentElementErrorEvent
     */
    public function setResult($result): self
    {
        $this->result = $result;

        return $this;
    }
}
