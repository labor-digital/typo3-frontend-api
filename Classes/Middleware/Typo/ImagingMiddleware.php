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
 * Last modified: 2021.06.24 at 11:23
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Middleware\Typo;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3fa\Core\ErrorHandler\ErrorHandler;
use LaborDigital\T3fa\Core\Imaging\RequestFactory;
use LaborDigital\T3fa\Core\Imaging\RequestHandler;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ImagingMiddleware implements MiddlewareInterface
{
    use ContainerAwareTrait;
    
    public const PATH_PREFIX = '/imaging-api';
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $givenPath = $request->getUri()->getPath();
        if (str_starts_with($givenPath, T3FA_IMAGING_ENDPOINT_PREFIX) && RequestFactory::getRequest() !== null) {
            return $this->makeInstance(ErrorHandler::class)->handleErrorsIn(function () {
                /** @var \LaborDigital\T3fa\Core\Imaging\Request $imagingRequest */
                $imagingRequest = RequestFactory::getRequest();
                
                $this->makeInstance(RequestHandler::class)->process($imagingRequest);
                
                // @todo would be nice to return a response here
                $imagingRequest->settleIfPossible();
                
                throw new NotFoundException();
            }, $request);
        }
        
        return $handler->handle($request);
    }
    
}