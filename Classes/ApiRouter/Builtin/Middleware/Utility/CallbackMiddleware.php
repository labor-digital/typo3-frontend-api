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
 * Last modified: 2020.09.28 at 17:33
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\Utility;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallbackMiddleware implements MiddlewareInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * CallbackMiddleware constructor.
     *
     * @param   callable  $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = call_user_func($this->callback, $request, $handler);

        if ($result instanceof ServerRequestInterface) {
            return $handler->handle($result);
        }

        return $result;
    }
}
