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


use Psr\Http\Message\ResponseInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class VerboseHandler extends AbstractHandler
{
    /**
     * The whoops instance to render errors with
     *
     * @var Run
     */
    protected $whoops;

    /**
     * True as long as the global listeners are active, otherwise we simply ignore them
     *
     * @var bool
     */
    protected $isGloballyActive = false;

    public function __construct(?Run $whoops = null)
    {
        $this->whoops = $whoops ?? GeneralUtility::makeInstance(Run::class);

        $this->whoops->allowQuit(false);
        $this->whoops->writeToOutput(false);
        $this->whoops->sendHttpCode(false);
        $this->whoops->register();

        register_shutdown_function(function () {
            if (! $this->isGloballyActive) {
                return;
            }

            $this->whoops->allowQuit(true);
            $this->whoops->writeToOutput(true);
            $this->whoops->sendHttpCode(true);

            // Allow cors requests in development environments
            if (Environment::getContext()->isDevelopment()) {
                header('Access-Control-Allow-Origin: *');
            }

            $this->whoops->{Run::SHUTDOWN_HANDLER}();
        });
    }

    /**
     * @inheritDoc
     */
    public function handle(callable $callback): ResponseInterface
    {
        if ($this->isNested) {
            return $callback();
        }

        $this->isGloballyActive = true;

        $this->prepareWhoops();

        try {
            return $callback();
        } catch (Throwable $e) {
            $error = $this->makeUnifiedError($e);
            $this->logError($error);
            $response = $this->getErrorResponse($error);

            try {
                $response->getBody()->write($this->whoops->{Run::EXCEPTION_HANDLER}($error->getError()));
            } catch (Throwable $e) {
                // An exception was thrown while handling an exception o.O
                // Try to disable the rendering of variables
                if ($this->isNested) {
                    throw $e;
                }

                if (method_exists(HtmlDumper::class, 'setDumpValues')) {
                    HtmlDumper::setDumpValues(false);
                    $response->getBody()->write($this->whoops->{Run::EXCEPTION_HANDLER}($error->getError()));
                    HtmlDumper::setDumpValues(true);
                } else {
                    $response->getBody()->write(
                        \GuzzleHttp\json_encode(
                            [
                                'errors' => [
                                    'status'        => 500,
                                    'title'         => 'An error while handling another error occurred and broke everything.',
                                    'subError'      => $e->getMessage(),
                                    'originalError' => $error->getMessage(),
                                    'stack'         => $error->getStack(),
                                ],
                            ]
                        )
                    );

                    // This is now always a json api result.
                    $response = $response->withHeader('Content-Type', 'application/vnd.api+json');
                }
            }

            return $this->filterErrorResponse($response, $error);
        } finally {
            $this->isGloballyActive = false;
        }
    }

    /**
     * Prepares the whoops instance to handle the current request
     */
    protected function prepareWhoops(): void
    {
        $this->whoops->clearHandlers();
        if ($this->doesAcceptHttp()) {
            $responseHandler = GeneralUtility::makeInstance(PrettyPageHandler::class);
        } else {
            $responseHandler = GeneralUtility::makeInstance(JsonResponseHandler::class);
            $responseHandler->setJsonApi(true);
            $responseHandler->addTraceToOutput(true);
        }
        $this->whoops->appendHandler($responseHandler);
    }

}
