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
 * Last modified: 2021.03.17 at 19:33
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Whoops\Handler;


use ErrorException;
use GuzzleHttp\Psr7\Utils;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PlainHandler extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function handle(callable $callback): ResponseInterface
    {
        if ($this->isNested) {
            return $callback();
        }

        $this->registerErrorHandler();

        try {
            return $callback();
        } catch (Throwable $e) {
            return $this->convertThrowableToResponse($e);
        } finally {
            $this->restoreErrorHandler();
        }
    }

    /**
     * Registers a global error handler to handle issues that can't be cached using the try{} block
     */
    protected function registerErrorHandler(): void
    {
        set_error_handler(function ($errorLevel, $errorMessage, $errorFile, $errorLine) {
            if ($errorLevel & error_reporting()) {
                GeneralUtility::makeInstance(EmitterInterface::class)->emit(
                    $this->convertThrowableToResponse(
                        new ErrorException($errorMessage, 0, $errorLevel, $errorFile, $errorLine)
                    )
                );

                exit();
            }
        });
    }

    /**
     * Restores the previous error handler
     */
    protected function restoreErrorHandler(): void
    {
        restore_error_handler();
    }

    /**
     * Does what it says, converts the given throwable into a decorated response object
     *
     * @param   \Throwable  $throwable
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function convertThrowableToResponse(Throwable $throwable): ResponseInterface
    {
        $error = $this->makeUnifiedError($throwable);
        $this->logError($error);
        $response = $this->getErrorResponse($error)->withHeader('Content-Type', 'application/vnd.api+json');

        // @todo in v10 this can be read from the typoContext object
        $isDev = Environment::getContext()->isDevelopment();

        $response = $response->withBody(
            Utils::streamFor(
                json_encode([
                    'errors' => [
                        'status' => $error->getStatusCode(),
                        'title'  => $response->getReasonPhrase(),
                    ],
                ], $isDev ? JSON_PRETTY_PRINT : 0)
            )
        );

        return $this->filterErrorResponse($response, $error);
    }
}
