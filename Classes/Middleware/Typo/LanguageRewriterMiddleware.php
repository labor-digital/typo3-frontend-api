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
 * Last modified: 2021.05.31 at 10:15
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Middleware\Typo;


use LaborDigital\T3fa\Core\Routing\Util\RequestRewriter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LanguageRewriterMiddleware implements MiddlewareInterface
{
    
    /**
     * @var \LaborDigital\T3fa\Core\Routing\Util\RequestRewriter
     */
    protected $requestRewriter;
    
    public function __construct(RequestRewriter $requestRewriter)
    {
        $this->requestRewriter = $requestRewriter;
    }
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($this->requestRewriter->rewriteLanguageAttribute($request));
    }
    
}