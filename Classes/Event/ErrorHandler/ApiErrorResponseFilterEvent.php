<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.06.25 at 18:22
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Event\ErrorHandler;


use LaborDigital\T3fa\Core\ErrorHandler\UnifiedError;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ApiErrorResponseFilterEvent
 *
 * Dispatched when the frontend api emits an error response.
 * Can be used to filter the response object
 *
 * @package LaborDigital\T3fa\Event\ErrorHandler
 */
class ApiErrorResponseFilterEvent
{
    /**
     * The error that lead to this event
     *
     * @var UnifiedError
     */
    protected $error;
    
    /**
     * The generated response
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;
    
    public function __construct(UnifiedError $error, ResponseInterface $response)
    {
        $this->error = $error;
        $this->response = $response;
    }
    
    /**
     * Returns the error that lead to this event
     *
     * @return \LaborDigital\T3fa\Core\ErrorHandler\UnifiedError
     */
    public function getError(): UnifiedError
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
     * @return ApiErrorResponseFilterEvent
     */
    public function setResponse(ResponseInterface $response): ApiErrorResponseFilterEvent
    {
        $this->response = $response;
        
        return $this;
    }
}
