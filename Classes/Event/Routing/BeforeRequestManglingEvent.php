<?php
/*
 * Copyright 2022 LABOR.digital
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
 * Last modified: 2022.08.29 at 15:39
 */

namespace LaborDigital\T3fa\Event\Routing;

use Psr\Http\Message\ServerRequestInterface;

class BeforeRequestManglingEvent
{
    
    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected ServerRequestInterface $request;
    
    /**
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }
    
    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
    
    /**
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}