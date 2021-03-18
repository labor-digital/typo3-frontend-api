<?php
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

namespace LaborDigital\Typo3FrontendApi\Whoops;

use LaborDigital\Typo3BetterApi\Container\ContainerAwareTrait;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\Whoops\Handler\AbstractHandler;
use LaborDigital\Typo3FrontendApi\Whoops\Handler\PlainHandler;
use LaborDigital\Typo3FrontendApi\Whoops\Handler\VerboseHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\SingletonInterface;

include __DIR__ . '/DummySymfonyVarDumper/include.php';

class ErrorHandler implements SingletonInterface, LoggerAwareInterface
{
    use ResponseFactoryTrait;
    use LoggerAwareTrait;
    use ContainerAwareTrait;

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
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $context;

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
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * ErrorHandler constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface         $container
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext                  $context
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(
        TypoContext $context,
        FrontendApiConfigRepository $configRepository
    ) {
        $this->context          = $context;
        $this->configRepository = $configRepository;
    }

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
        $isNested   = static::$nestingLevel++ > 0;
        $handler    = $this->resolveHandler();
        $oldContext = $handler->setContext($request, $isNested);

        try {
            // Allow cors requests in development environments
            if ($this->isDevMode()) {
                header('Access-Control-Allow-Origin: *');
            }

            ob_start();
            $level = ob_get_level();

            $response = $handler->handle($wrapper);

            while (ob_get_level() >= $level) {
                ob_end_clean();
            }

            // Allow cors requests in development environments
            if ($this->isDevMode()) {
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
        return $this->context->Env()->isDev();
    }

    /**
     * Returns true if the verbose handler should be used, false if the plain handler is used instead
     *
     * @return bool
     */
    protected function useVerboseHandler(): bool
    {
        // Check if we should use the speaking error handler
        $useSpeakingErrorHandler = $this->configRepository->routing()->useSpeakingErrorHandler();
        if ($useSpeakingErrorHandler === null) {
            // Check if we are running in dev mode
            $useSpeakingErrorHandler = $this->isDevMode() || $this->context->Env()->isFeDebug();

            // Check if we got an admin user
            if (! $useSpeakingErrorHandler && $this->context->BeUser()->isAdmin()) {
                $useSpeakingErrorHandler = true;
            }
        }

        return $useSpeakingErrorHandler;
    }

    /**
     * Internal helper to resolve the correct error handler instance based on the configuration
     *
     * @return \LaborDigital\Typo3FrontendApi\Whoops\Handler\AbstractHandler
     */
    protected function resolveHandler(): AbstractHandler
    {
        $handlerClass = $this->useVerboseHandler() ? static::$verboseHandlerClass : static::$plainHandlerClass;

        if ($this->handlers[$handlerClass]) {
            return $this->handlers[$handlerClass];
        }

        return $this->handlers[$handlerClass] = $this->getInstanceOf($handlerClass);
    }
}
