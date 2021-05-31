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
 * Last modified: 2021.05.28 at 21:00
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Middleware\Typo;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Core\ErrorHandler\ErrorHandler;
use LaborDigital\T3fa\Core\Routing\ApiBootstrap;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ApiForkMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use TypoContextAwareTrait;
    use LoggerAwareTrait;
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $apiPath = $this->getTypoContext()->config()->getConfigValue('t3fa.routing.apiPath');
        $givenPath = $request->getUri()->getPath();
        
        if (str_starts_with($givenPath, $apiPath)) {
            $this->logger->debug('Handle api request: [' . $request->getMethod() . '] ' . $request->getUri());
            
            return $this->forkRequest($request);
        }
        
        return $handler->handle($request);
    }
    
    /**
     * Initializes the api router instance and redirects the request to be handled by it
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function forkRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getService(ErrorHandler::class)->handleErrorsIn(function () use ($request) {
            return $this->getService(ApiBootstrap::class)->boot($request);
        }, $request);
    }
}