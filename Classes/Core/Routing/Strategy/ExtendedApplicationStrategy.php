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
 * Last modified: 2021.06.10 at 21:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing\Strategy;


use LaborDigital\T3fa\Core\Routing\Util\ResponseFactoryTrait;
use League\Route\Route;
use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\OptionsHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExtendedApplicationStrategy extends ApplicationStrategy implements OptionsHandlerInterface
{
    use ResponseFactoryTrait;
    
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());
        
        $vars = array_merge(
            $request->getAttribute('staticRouteAttributes', []),
            $route->getVars()
        );
        
        $response = $controller($request, $vars);
        
        return $this->decorateResponse($response);
    }
    
    public function getOptionsCallable(array $methods): callable
    {
        return function () use ($methods): ResponseInterface {
            $options = implode(', ', $methods);
            $response = $this->getResponse();
            $response = $response->withHeader('allow', $options);
            
            return $response->withHeader('access-control-allow-methods', $options);
        };
    }
}