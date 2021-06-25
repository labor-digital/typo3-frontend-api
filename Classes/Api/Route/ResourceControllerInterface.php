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
 * Last modified: 2021.06.25 at 18:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Route;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResourceControllerInterface extends PublicServiceInterface
{
    /**
     * Handles the request for a single resource
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function singleAction(ServerRequestInterface $request, array $vars): ResponseInterface;
    
    /**
     * Handles the request for a resource collection
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function collectionAction(ServerRequestInterface $request, array $vars): ResponseInterface;
    
    /**
     * Handles the relationship action for a single relation field
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function relationshipAction(ServerRequestInterface $request, array $vars): ResponseInterface;
    
    /**
     * Handles the relation resolution on a single related field
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function relationAction(ServerRequestInterface $request, array $vars): ResponseInterface;
}