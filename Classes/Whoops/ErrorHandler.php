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

use ErrorException;
use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3BetterApi\Event\Events\ErrorFilterEvent;
use LaborDigital\Typo3BetterApi\NotImplementedException;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use LaborDigital\Typo3FrontendApi\Event\ApiErrorFilterEvent;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use League\Route\Http\Exception;
use League\Route\Http\Exception\HttpExceptionInterface;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\EventBus\EventBusInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Error\Http\ForbiddenException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\Http\UnauthorizedException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\SingletonInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use function GuzzleHttp\Psr7\stream_for;

include __DIR__ . '/DummySymfonyVarDumper/include.php';

class ErrorHandler implements SingletonInterface
{
    use ResponseFactoryTrait;

    /**
     * True if the current client accepts html responses
     *
     * @var bool
     */
    protected $acceptsHtml = false;

    /**
     * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
     */
    protected $container;

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $context;

    /**
     * True if the shutdown function was registered
     *
     * @var bool
     */
    protected static $hasShutdownFunction = false;

    /**
     * The number of nested calls we are running in.
     * Nested calls will simply re-throw the exception so the outer-most instance of this can handle it.
     *
     * @var int
     */
    protected static $nestingLevel = 0;

    /**
     * If this is true our dummy var dumper should not render any parameters while printing
     * the whoops screen
     *
     * @var bool
     */
    protected static $renderValues = true;

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
        TypoContainerInterface $container,
        TypoContext $context,
        FrontendApiConfigRepository $configRepository
    ) {
        $this->container        = $container;
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
        $localNestingLevel = static::$nestingLevel++;
        try {
            // Check if the client accepts http responses
            $accept            = $request->getHeaderLine('Accept');
            $this->acceptsHtml = stripos($accept, 'text/html') !== false || stripos($accept, 'application/xhtml+xml') !== false;

            // Allow cors requests in development environments
            if (Environment::getContext()->isDevelopment()) {
                header('Access-Control-Allow-Origin: *');
            }

            // Check if we should use the speaking error handler
            $useSpeakingErrorHandler = $this->configRepository->routing()->useSpeakingErrorHandler();
            if ($useSpeakingErrorHandler === null) {
                // Check if we are running in dev mode
                $useSpeakingErrorHandler = $this->context->Env()->isDev();

                // Check if we got an admin user
                if (! $useSpeakingErrorHandler && $this->context->BeUser()->isAdmin()) {
                    $useSpeakingErrorHandler = true;
                }

            }

            // Block all outputs
            ob_start();
            $level = ob_get_level();

            // Handle the request
            if ($useSpeakingErrorHandler) {
                $response = $this->handleSpeaking($wrapper, $localNestingLevel);
            } else {
                $response = $this->handleNonSpeaking($wrapper, $localNestingLevel);
            }

            // Stop output blocking
            while (ob_get_level() >= $level) {
                ob_end_clean();
            }

            // Allow cors requests in development environments
            if (Environment::getContext()->isDevelopment()) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            }

            // Done
            return $response;
        } catch (Throwable $e) {
            throw $e;
        } finally {
            static::$nestingLevel--;
        }
    }

    public static function renderValues(): bool
    {
        return static::$renderValues;
    }

    /**
     * Internal factory to create a new whoops instance based on the request and handler objects
     *
     * @return \Whoops\Run
     */
    protected function makeWhoops(): Run
    {
        // Try to select the type of response handler
        if ($this->acceptsHtml) {
            $responseHandler = $this->container->get(PrettyPageHandler::class);
        } else {
            $responseHandler = $this->container->get(JsonResponseHandler::class);
            $responseHandler->setJsonApi(true);
            $responseHandler->addTraceToOutput(true);
        }

        // Create new instance
        $whoops = $this->container->get(Run::class);
        $whoops->appendHandler($responseHandler);

        return $whoops;
    }

    /**
     * Is used as outer wrapper for the given wrapper instance when we should use speaking error handling.
     *
     * @param   callable  $wrapper
     *
     * @param   int       $localNestingLevel
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Throwable
     */
    protected function handleSpeaking(callable $wrapper, int $localNestingLevel): ResponseInterface
    {
        // Prepare whoops instance
        $whoops = $this->makeWhoops();
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->sendHttpCode(false);
        $whoops->register();

        // Register shutdown function
        if (! static::$hasShutdownFunction) {
            static::$hasShutdownFunction = true;
            $shutdownFunction            = static function () use ($whoops) {
                $whoops->allowQuit(true);
                $whoops->writeToOutput(true);
                $whoops->sendHttpCode(true);

                // Allow cors requests in development environments
                if (Environment::getContext()->isDevelopment()) {
                    header('Access-Control-Allow-Origin: *');
                }

                $whoops->{Run::SHUTDOWN_HANDLER}();
            };
            register_shutdown_function($shutdownFunction);
        }

        try {
            $response = $wrapper();
        } catch (Throwable $exception) {
            if ($localNestingLevel > 0) {
                throw $exception;
            }
            $exception         = $this->translateImmediateResponseException($exception);
            $responseException = $this->translateTypoError($exception);
            $response          = $this->getResponse(
                method_exists($responseException, 'getStatusCode') ? $responseException->getStatusCode() : 500);
            try {
                $response->getBody()->write($whoops->{Run::EXCEPTION_HANDLER}($exception));
            } catch (Throwable $e) {
                // An exception was thrown while handling an exception o.O
                // Try to disable the rendering of variables
                if ($localNestingLevel > 0) {
                    throw $e;
                }
                static::$renderValues = false;
                $response->getBody()->write($whoops->{Run::EXCEPTION_HANDLER}($exception));
                static::$renderValues = true;
            }
            $response = $response->withHeader('Content-Type', $this->acceptsHtml ? 'text/html' : 'application/vnd.api+json');
            $response = $this->responseFilter($response, $exception);
        }

        // Disable error handler
        $whoops->unregister();

        // Done
        return $response;
    }

    /**
     * Is used as outer wrapper for the given wrapper instance when we should use non-speaking error handling.
     *
     * @param   callable  $wrapper
     *
     * @param   int       $localNestingLevel
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Throwable
     */
    protected function handleNonSpeaking(callable $wrapper, int $localNestingLevel): ResponseInterface
    {
        // Set our internal error handler
        set_error_handler(function ($errorLevel, $errorMessage, $errorFile, $errorLine) {
            if ($errorLevel & error_reporting()) {
                $response = $this->decorateNonSpeakingError(
                    new ErrorException($errorMessage, 0, $errorLevel, $errorFile, $errorLine)
                );
                $this->container->get(EmitterInterface::class)->emit($response);
                exit();
            }
        });

        try {
            $response = $wrapper();
        } catch (Throwable $exception) {
            if ($localNestingLevel > 0) {
                throw $exception;
            }
            $exception = $this->translateImmediateResponseException($exception);
            $response  = $this->decorateNonSpeakingError($exception);
        }

        // Restore the error handler
        restore_error_handler();

        // Done
        return $response;
    }

    /**
     * Is used to decorate the received error element and return a formatted response object from it
     * It will also automatically convert the typo3 http exceptions into their matching equivalent of the Route package
     *
     * @param   \Throwable  $error
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \JsonException
     */
    protected function decorateNonSpeakingError(Throwable $error): ResponseInterface
    {
        // Translate typo3 exceptions
        $error = $this->translateTypoError($error);

        // Check if this is a http exception
        if (! $error instanceof HttpExceptionInterface) {
            $statusCode = method_exists($error, 'getStatusCode') ? $error->getStatusCode() : 500;
            $error      = new Exception($statusCode, '', ($error instanceof \Exception ? $error : null));
        }

        // Create the response
        $response = $this->getResponse($error->getStatusCode())
                         ->withHeader('Content-Type', 'application/vnd.api+json');
        $body     = [
            'errors' => [
                'status' => $error->getStatusCode(),
                'title'  => $response->getReasonPhrase(),
            ],
        ];
        $response = $response->withBody(stream_for(json_encode($body, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)));

        // Filter the response
        return $this->responseFilter($response, $error);
    }

    /**
     * Translates some special typo3 exceptions into their http counterparts
     *
     * @param   \Throwable  $error
     *
     * @return \Throwable
     */
    protected function translateTypoError(Throwable $error): Throwable
    {
        $translations = [
            BadRequestException::class         => Exception\BadRequestException::class,
            ForbiddenException::class          => Exception\ForbiddenException::class,
            PageNotFoundException::class       => NotFoundException::class,
            ServiceUnavailableException::class => NotFoundException::class,
            UnauthorizedException::class       => Exception\UnauthorizedException::class,
            NotImplementedException::class     => NotFoundException::class,
        ];
        $statusMap    = [
            ServiceUnavailableException::class => 503,
            NotImplementedException::class     => 501,
        ];
        foreach ($translations as $from => $to) {
            if ($error instanceof $from) {
                $code = $error->getCode();
                if (empty($code)) {
                    $code = ! empty($statusMap[$from]) ? $statusMap[$from] : 0;
                }
                $error = new $to($error->getMessage(), $error, $code);
                break;
            }
        }

        return $error;
    }

    /**
     * Allow other services to handle our error and modify the response
     *
     * @param   \Psr\Http\Message\ResponseInterface  $response
     * @param   \Throwable                           $exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function responseFilter(ResponseInterface $response, Throwable $exception): ResponseInterface
    {
        $eventBus = $this->container->get(EventBusInterface::class);

        // Emit event
        $eventBus->dispatch(($e = new ApiErrorFilterEvent($exception, $response)));
        $response = $e->getResponse();

        // Emit the global event
        // The Result is given for compatibility but is noop here...
        if ($e->isEmitErrorEvent()) {
            $eventBus->dispatch(new ErrorFilterEvent($exception, null));
        }

        // Done
        return $response;
    }

    /**
     * Internal helper to handle immediate response exceptions like normal http exceptions
     *
     * @param   \Throwable  $error
     *
     * @return \Throwable
     */
    protected function translateImmediateResponseException(Throwable $error): Throwable
    {
        // Translate immediate response exceptions
        if ($error instanceof ImmediateResponseException) {
            $error = new Exception($error->getResponse()->getStatusCode(),
                $error->getResponse()->getReasonPhrase(), $error, $error->getResponse()->getHeaders());
        }

        return $error;
    }
}
