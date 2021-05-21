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
 * Last modified: 2021.05.12 at 16:42
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Core\ErrorHandler\Handler;


use ErrorException;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Core\EventBus\TypoEventBus;
use LaborDigital\T3fa\Core\ErrorHandler\UnifiedError;
use LaborDigital\T3fa\Core\Routing\Util\ResponseFactoryTrait;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

abstract class AbstractHandler implements LoggerAwareInterface, NoDiInterface
{
    use ResponseFactoryTrait;
    use LoggerAwareTrait;
    use ContainerAwareTrait;
    
    /**
     * The server request we should handle errors for
     *
     * @var ServerRequestInterface|null
     */
    protected $request;
    
    /**
     * True if the handler is executed inside another handler
     *
     * @var bool
     */
    protected $isNested = false;
    
    /**
     * True if the current handler runs in a development environment
     *
     * @var bool
     */
    protected $isDev = false;
    
    /**
     * Sets contextual data for the current execution.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface|null  $request
     * @param   bool                                           $isNested
     */
    public function setContext(?ServerRequestInterface $request, bool $isNested, bool $isDev): array
    {
        $current = [$this->request, $this->isNested, $this->isDev];
        $this->request = $request;
        $this->isNested = $isNested;
        $this->isDev = $isDev;
        
        return $current;
    }
    
    /**
     * @inheritDoc
     */
    public function handle(callable $callback): ResponseInterface
    {
        if ($this->isNested) {
            return $callback();
        }
        
        $errorProcessor = function (Throwable $throwable): ResponseInterface {
            $error = $this->makeInstance(UnifiedError::class, [$throwable, $this->request]);
            $this->logError($error);
            
            return $this->filterErrorResponse(
                $this->makeResponse($error), $error
            );
        };
        
        set_error_handler(function ($errorLevel, $errorMessage, $errorFile, $errorLine) use ($errorProcessor) {
            if ($errorLevel & error_reporting()) {
                $this->makeInstance(SapiEmitter::class)->emit(
                    $errorProcessor(
                        new ErrorException($errorMessage, 0, $errorLevel, $errorFile, $errorLine)
                    )
                );
                
                exit();
            }
        });
        
        try {
            return $callback();
        } catch (Throwable $e) {
            return $errorProcessor($e);
        } finally {
            restore_error_handler();
        }
    }
    
    /**
     * Implementation method to for the different error handlers to build the actual error response with
     *
     * @param   \LaborDigital\T3fa\Core\ErrorHandler\UnifiedError  $error
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function makeResponse(UnifiedError $error): ResponseInterface;
    
    /**
     * Returns true if A a request is currently set, and B, if it accepts a html response
     *
     * @return bool
     */
    protected function doesAcceptHttp(): bool
    {
        if (! $this->request) {
            return false;
        }
        
        $accept = $this->request->getHeaderLine('Accept');
        
        return stripos($accept, 'text/html') !== false || stripos($accept, 'application/xhtml+xml') !== false;
    }
    
    /**
     * Prepares a new response object based on the given unified error instance.
     *
     * @param   UnifiedError  $error
     * @param   bool          $forceJson  If set to true the content type will always be a json
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getErrorResponse(UnifiedError $error, bool $forceJson = false): ResponseInterface
    {
        return $this->getResponse($error->getStatusCode())->withHeader(
            'Content-Type', ! $forceJson && $this->doesAcceptHttp()
            ? 'text/html' : 'application/vnd.api+json');
        
    }
    
    /**
     * Triggers the ApiErrorFilterEvent and the ErrorFilterEvent events to allow output filtering and
     * notifies other event listeners that an error occurred
     *
     * @param   \Psr\Http\Message\ResponseInterface  $response
     * @param   UnifiedError                         $error
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function filterErrorResponse(ResponseInterface $response, UnifiedError $error): ResponseInterface
    {
        // Emit event
        $eventBus = TypoEventBus::getInstance();
        // @todo reimplement this
//        $eventBus->dispatch(($e = new ApiErrorFilterEvent($error->getError(), $response)));
//        $response = $e->getResponse();
//
//        // Emit the global event
//        // The Result is given for compatibility but is noop here...
//        if ($e->isEmitErrorEvent()) {
//            $eventBus->dispatch(new ErrorFilterEvent($error->getError(), null));
//        }
        
        return $response;
    }
    
    /**
     * Internal helper to log an error
     *
     * @param   UnifiedError  $error
     */
    protected function logError(UnifiedError $error): void
    {
        $context = $error->getLogContext();
        $message = $error->getMessage();
        
        $statusCode = $error->getStatusCode();
        if ($statusCode >= 400) {
            if ($statusCode >= 500) {
                $this->logger->critical($message, $context);
            } elseif ($statusCode === 404) {
                $this->logger->warning($message, $context);
            } else {
                $this->logger->error($message, $context);
            }
        } else {
            $this->logger->info($message, $context);
        }
    }
    
}
