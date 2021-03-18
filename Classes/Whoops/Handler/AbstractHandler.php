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
 * Last modified: 2021.03.17 at 17:52
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Whoops\Handler;


use LaborDigital\Typo3BetterApi\Event\Events\ErrorFilterEvent;
use LaborDigital\Typo3BetterApi\Event\TypoEventBus;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use LaborDigital\Typo3FrontendApi\Event\ApiErrorFilterEvent;
use LaborDigital\Typo3FrontendApi\Whoops\UnifiedError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractHandler implements LoggerAwareInterface
{
    use ResponseFactoryTrait;
    use LoggerAwareTrait;

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
     * Sets contextual data for the current execution.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface|null  $request
     * @param   bool                                           $isNested
     */
    public function setContext(?ServerRequestInterface $request, bool $isNested): array
    {
        $current        = [$this->request, $this->isNested];
        $this->request  = $request;
        $this->isNested = $isNested;

        return $current;
    }

    /**
     * Implementation that executes the given $callback, while wrapping it in an error sandbox.
     * The result is either the response result of the callback or a response containing an error.
     *
     * @param   callable  $callback  The callable to execute and monitor for exceptions
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract public function handle(callable $callback): ResponseInterface;

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
     * Creates a new unified error instance for the given throwable
     *
     * @param   \Throwable  $throwable
     *
     * @return \LaborDigital\Typo3FrontendApi\Whoops\UnifiedError
     */
    protected function makeUnifiedError(Throwable $throwable): UnifiedError
    {
        return GeneralUtility::makeInstance(UnifiedError::class, $throwable, $this->request);
    }

    /**
     * Prepares a new response object based on the given unified error instance.
     *
     * @param   \LaborDigital\Typo3FrontendApi\Whoops\UnifiedError  $error
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getErrorResponse(UnifiedError $error): ResponseInterface
    {
        $response = $this->getResponse($error->getStatusCode());
        $response->withHeader('Content-Type', $this->doesAcceptHttp() ? 'text/html' : 'application/vnd.api+json');

        return $response;
    }

    /**
     * Triggers the ApiErrorFilterEvent and the ErrorFilterEvent events to allow output filtering and
     * notifies other event listeners that an error occurred
     *
     * @param   \Psr\Http\Message\ResponseInterface                 $response
     * @param   \LaborDigital\Typo3FrontendApi\Whoops\UnifiedError  $error
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function filterErrorResponse(ResponseInterface $response, UnifiedError $error): ResponseInterface
    {
        // Emit event
        $eventBus = TypoEventBus::getInstance();
        $eventBus->dispatch(($e = new ApiErrorFilterEvent($error->getError(), $response)));
        $response = $e->getResponse();

        // Emit the global event
        // The Result is given for compatibility but is noop here...
        if ($e->isEmitErrorEvent()) {
            $eventBus->dispatch(new ErrorFilterEvent($error->getError(), null));
        }

        return $response;
    }

    /**
     * Internal helper to log an error
     *
     * @param   \LaborDigital\Typo3FrontendApi\Whoops\UnifiedError  $error
     */
    protected function logError(UnifiedError $error): void
    {
        $statusCode = $error->getStatusCode();
        if ($statusCode >= 400) {
            if ($statusCode >= 500) {
                $this->logger->critical($error->getMessage(), $error->getLogContext());
            } elseif ($statusCode === 404) {
                $this->logger->warning($error->getMessage(), $error->getLogContext());
            } else {
                $this->logger->error($error->getMessage(), $error->getLogContext());
            }
        } else {
            $this->logger->info($error->getMessage(), $error->getLogContext());
        }
    }

}
