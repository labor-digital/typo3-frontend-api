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
 * Last modified: 2021.05.31 at 10:02
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Middleware\Api;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AttributeProviderMiddleware implements MiddlewareInterface, NoDiInterface
{
    /**
     * @var array
     */
    protected $attributes;
    
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('staticRouteAttributes', $this->attributes);
        
        return $handler->handle($request);
    }
    
}