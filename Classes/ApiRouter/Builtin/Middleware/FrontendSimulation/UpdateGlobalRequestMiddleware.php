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
 * Last modified: 2020.09.28 at 17:31
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\FrontendSimulation;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateGlobalRequestMiddleware implements MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $GLOBALS['TYPO3_REQUEST']          = $request;
            $GLOBALS['TYPO3_REQUEST_FALLBACK'] = $request;

            return $handler->handle($request);
        } finally {
            $GLOBALS['TYPO3_REQUEST']          = $request;
            $GLOBALS['TYPO3_REQUEST_FALLBACK'] = $request;
        }
    }
}
