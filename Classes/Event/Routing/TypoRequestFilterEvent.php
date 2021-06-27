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
 * Last modified: 2021.06.25 at 19:05
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Event\Routing;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class TypoRequestFilterEvent
 *
 * Emitted when the API bootstrap creates a request clone to boot up TYPO3 with.
 * This allows you to apply last minute changes before TYPO actually boots.
 *
 * @package LaborDigital\T3fa\Event\Routing
 */
class TypoRequestFilterEvent
{
    /**
     * The modified request, used to boot up the TYPO3 core
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $typoRequest;
    
    /**
     * The actual request that we used as a base for the TYPO3 request
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $originalRequest;
    
    public function __construct(ServerRequestInterface $typoRequest, ServerRequestInterface $originalRequest)
    {
        $this->typoRequest = $typoRequest;
        $this->originalRequest = $originalRequest;
    }
    
    /**
     * Returns the actual request that we used as a base for the TYPO3 request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getOriginalRequest(): ServerRequestInterface
    {
        return $this->originalRequest;
    }
    
    /**
     * Returns the modified request, used to boot up the TYPO3 core
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getTypoRequest(): ServerRequestInterface
    {
        return $this->typoRequest;
    }
    
    /**
     * Allows you to override the modified request, used to boot up the TYPO3 core
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $typoRequest
     *
     * @return TypoRequestFilterEvent
     */
    public function setTypoRequest(ServerRequestInterface $typoRequest): TypoRequestFilterEvent
    {
        $this->typoRequest = $typoRequest;
        
        return $this;
    }
}