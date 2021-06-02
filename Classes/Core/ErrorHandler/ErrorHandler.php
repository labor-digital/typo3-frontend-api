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
 * Last modified: 2021.06.02 at 21:34
 */

declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.08.26 at 19:37
 */

namespace LaborDigital\T3fa\Core\ErrorHandler;

use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Core\ErrorHandler\Handler\AbstractHandler;
use LaborDigital\T3fa\Core\ErrorHandler\Handler\PlainHandler;
use LaborDigital\T3fa\Core\ErrorHandler\Handler\VerboseHandler;
use LaborDigital\T3fa\Core\Routing\Util\ResponseFactoryTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\SingletonInterface;

class ErrorHandler implements SingletonInterface, LoggerAwareInterface, PublicServiceInterface
{
    use ResponseFactoryTrait;
    use LoggerAwareTrait;
    use ContainerAwareTrait;
    use TypoContextAwareTrait;
    
    /**
     * The name of the class that should be used to handle errors verbosely
     *
     * @var string
     */
    public static $verboseHandlerClass = VerboseHandler::class;
    
    /**
     * The production error handler that only shows a bare minimum of information
     *
     * @var string
     */
    public static $plainHandlerClass = PlainHandler::class;
    
    /**
     * The list of instantiated handlers
     *
     * @var AbstractHandler[]
     */
    protected $handlers = [];
    
    /**
     * The number of nested calls we are running in.
     * Nested calls will simply re-throw the exception so the outer-most instance of this can handle it.
     *
     * @var int
     */
    protected static $nestingLevel = 0;
    
    /**
     * Can be used to execute the error handler for a given wrapper function.
     * The wrapper will be executed inside a try/catch block and all errors will be handled by the handler
     *
     * @param   callable                                  $wrapper
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Throwable
     */
    public function handleErrorsIn(callable $wrapper, ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->resolveHandler();
        $isDev = $this->isDevMode();
        $oldContext = $handler->setContext($request, static::$nestingLevel++ > 0, $isDev);
        
        try {
            // Allow cors requests in development environments
            if ($isDev) {
                header('Access-Control-Allow-Origin: *');
            }
            
            ob_start();
            $level = ob_get_level();
            
            $response = $handler->handle($wrapper);
            
            while (ob_get_level() >= $level) {
                ob_end_clean();
            }
            
            // We have to reset the response code because otherwise
            // our response code in the respoonse object will not be emitted correctly
            http_response_code(200);
            
            // Allow cors requests in development environments
            if ($isDev) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            }
            
            // Done
            return $response;
        } catch (Throwable $e) {
            throw $e;
        } finally {
            static::$nestingLevel--;
            $handler->setContext(...$oldContext);
        }
    }
    
    /**
     * Returns true if the application runs in a development context, false if not
     *
     * @return bool
     */
    protected function isDevMode(): bool
    {
        return $this->getTypoContext()->env()->isDev();
    }
    
    /**
     * Returns true if the verbose handler should be used, false if the plain handler is used instead
     *
     * @return bool
     */
    protected function useVerboseHandler(): bool
    {
        // Check if we should use the speaking error handler
        $useVerboseHandler = $this->getTypoContext()->config()->getConfigValue('t3fa.routing.useSpeakingErrorHandler');
        
        if ($useVerboseHandler === null) {
            $context = $this->getTypoContext();
            
            // Check if we are running ina verbose environment
            $useVerboseHandler = $this->isDevMode() || $context->env()->isFeDebug();
            
            // Check if we got an admin user
            if (! $useVerboseHandler && $context->beUser()->isAdmin()) {
                $useVerboseHandler = true;
            }
        }
        
        return $useVerboseHandler;
    }
    
    /**
     * Internal helper to resolve the correct error handler instance based on the configuration
     *
     * @return AbstractHandler
     */
    protected function resolveHandler(): AbstractHandler
    {
        $handlerClass = $this->useVerboseHandler() ? static::$verboseHandlerClass : static::$plainHandlerClass;
        
        if ($this->handlers[$handlerClass]) {
            return $this->handlers[$handlerClass];
        }
        
        /** @var AbstractHandler $handler */
        $handler = $this->makeInstance($handlerClass);
        $handler->setLogger($this->logger);
        
        return $this->handlers[$handlerClass] = $handler;
    }
}
