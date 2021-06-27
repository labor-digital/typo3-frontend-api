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
 * Last modified: 2021.06.10 at 21:15
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Middleware\Api;

use League\Route\Http\Exception\BadRequestException;
use Neunerlei\Arrays\ArrayGeneratorException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SimpleXMLElement;
use Throwable;

class BodyParserMiddleware implements MiddlewareInterface
{
    
    /**
     * The list of all registered parsers
     *
     * @var callable[]
     */
    protected $parsers = [];
    
    /**
     * The HTTP methods where this middleware should be active
     *
     * @var string[]
     */
    protected $activeMethods = ['POST', 'PUT', 'DELETE'];
    
    /**
     * Registers a new body parser that is used for the given content types
     *
     * @param   array     $contentTypes  The list of content types this parser should apply to
     * @param   callable  $parser        The parser callback, which receives the request object and should return the updated version of it
     *
     * @return $this
     */
    public function addParser(array $contentTypes, callable $parser): self
    {
        foreach ($contentTypes as $contentType) {
            $this->parsers[$contentType] = $parser;
        }
        
        return $this;
    }
    
    /**
     * Returns the list of all registered parsers, ordered by their respective content types.
     * This will also include all built-in default parsers
     *
     * @return callable[]
     */
    public function getParsers(): array
    {
        if (empty($this->parsers)) {
            $this->registerDefaultParsers();
        }
        
        return $this->parsers;
    }
    
    /**
     * Returns the configured HTTP methods where this middleware should be active
     *
     * @return string[]
     */
    public function getActiveMethods(): array
    {
        return $this->activeMethods;
    }
    
    /**
     * Sets the HTTP methods where this middleware should be active
     *
     * @param   string[]  $activeMethods
     *
     * @return BodyParserMiddleware
     */
    public function setActiveMethods(array $activeMethods): BodyParserMiddleware
    {
        $this->activeMethods = $activeMethods;
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! empty($request->getParsedBody())) {
            return $handler->handle($request);
        }
        
        if (! in_array($request->getMethod(), $this->activeMethods, true)) {
            return $handler->handle($request);
        }
        
        $parsers = $this->getParsers();
        $contentType = $request->getHeaderLine('Content-Type');
        
        foreach ($parsers as $parserContentType => $parser) {
            if (stripos($contentType, $parserContentType) !== 0) {
                continue;
            }
            $request = $parsers[$parserContentType]($request);
            break;
        }
        
        return $handler->handle($request);
    }
    
    /**
     * Registers the default parsers for the most common content types
     */
    protected function registerDefaultParsers(): void
    {
        $this->addParser(['application/x-www-form-urlencoded'], static function (ServerRequestInterface $request) {
            $content = trim((string)$request->getBody());
            
            if (empty($content)) {
                return $request->withParsedBody([]);
            }
            
            parse_str($content, $data);
            
            if (empty($data)) {
                throw new BadRequestException('Invalid form data given');
            }
            
            return $request->withParsedBody($data);
        });
        
        $this->addParser(['application/json'], static function (ServerRequestInterface $request) {
            try {
                $content = Arrays::makeFromJson($request->getBody()->getContents());
                
                return $request->withParsedBody($content);
            } catch (ArrayGeneratorException $exception) {
                throw new BadRequestException('Invalid JSON data given');
            }
        });
        
        $this->addParser(['application/xml', 'text/xml', 'application/x-xml'], static function (ServerRequestInterface $request) {
            $content = trim((string)$request->getBody());
            
            if (empty($content)) {
                return $request;
            }
            
            try {
                return $request->withParsedBody(new SimpleXMLElement($content));
            } catch (Throwable $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }
}
