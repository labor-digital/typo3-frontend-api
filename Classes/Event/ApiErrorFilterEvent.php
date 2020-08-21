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
 * Last modified: 2020.03.20 at 21:46
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Class ApiErrorFilterEvent
 *
 * Dispatched when the frontend api emits an exception.
 * Can be used to filter the response object
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ApiErrorFilterEvent
{
    /**
     * The error that lead to this event
     *
     * @var \Throwable
     */
    protected $error;

    /**
     * The generated response
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * True if the ErrorFilterEvent should be emitted, false if not
     *
     * @var bool
     */
    protected $emitErrorEvent = true;

    /**
     * ApiErrorFilterEvent constructor.
     *
     * @param   \Throwable                           $error
     * @param   \Psr\Http\Message\ResponseInterface  $response
     */
    public function __construct(Throwable $error, ResponseInterface $response)
    {
        $this->error    = $error;
        $this->response = $response;
    }

    /**
     * Returns the error that lead to this event
     *
     * @return \Throwable
     */
    public function getError(): Throwable
    {
        return $this->error;
    }

    /**
     * Returns the generated response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Replaces the generated response
     *
     * @param   \Psr\Http\Message\ResponseInterface  $response
     *
     * @return ApiErrorFilterEvent
     */
    public function setResponse(ResponseInterface $response): ApiErrorFilterEvent
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Returns true if the ErrorFilterEvent should be emitted, false if not
     *
     * @return bool
     */
    public function isEmitErrorEvent(): bool
    {
        return $this->emitErrorEvent;
    }

    /**
     * Sets if the ErrorFilterEvent should be emitted
     *
     * @param   bool  $emitErrorEvent
     *
     * @return ApiErrorFilterEvent
     */
    public function setEmitErrorEvent(bool $emitErrorEvent): ApiErrorFilterEvent
    {
        $this->emitErrorEvent = $emitErrorEvent;

        return $this;
    }
}
