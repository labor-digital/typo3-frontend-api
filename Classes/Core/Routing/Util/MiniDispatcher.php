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
 * Last modified: 2021.06.21 at 13:21
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing\Util;

use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Class MiniDispatcher
 *
 * A helper to create a tiny, internal middleware dispatcher to handle the required TYPO middlewares
 *
 * @package LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\FrontendSimulation
 */
class MiniDispatcher implements RequestHandlerInterface
{
    use ContainerAwareTrait;
    
    /**
     * The list of registered middleware instances or middleware class names
     *
     * @var string[]|MiddlewareInterface[]
     */
    public $middlewares = [];
    
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->middlewares);
        if (is_string($middleware)) {
            $middleware = $this->getServiceOrInstance($middleware);
        }
        
        if (! is_object($middleware)) {
            throw new RuntimeException('No more middlewares left');
        }
        
        return $middleware->process($request, $this);
    }
}
