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
 * Last modified: 2020.03.20 at 19:45
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use LaborDigital\Typo3FrontendApi\Event\Traits\ContentElementFilterTrait;

/**
 * Class ContentElementAfterControllerFilterEvent
 *
 * Dispatched after the content element handler generated the content of a content element
 * using the registered controller class, but before the post processing takes place.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ContentElementAfterControllerFilterEvent
{
    use ContentElementFilterTrait;

    /**
     * The result that was returned by the controller action
     *
     * @var mixed
     */
    protected $result;

    /**
     * ContentElementAfterControllerFilterEvent constructor.
     *
     * @param   mixed                              $result
     * @param   ContentElementControllerInterface  $controller
     * @param   ContentElementControllerContext    $context
     * @param   bool                               $isFrontend
     */
    public function __construct($result, ContentElementControllerInterface $controller, ContentElementControllerContext $context, bool $isFrontend)
    {
        $this->result     = $result;
        $this->controller = $controller;
        $this->context    = $context;
        $this->isFrontend = $isFrontend;
    }

    /**
     * Returns the result that was returned by the controller action
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Used to update the result that will be post processed and returned to the client
     *
     * @param   mixed  $result
     *
     * @return ContentElementAfterControllerFilterEvent
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
