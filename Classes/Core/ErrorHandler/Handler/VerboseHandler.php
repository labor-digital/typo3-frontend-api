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
 * Last modified: 2021.05.12 at 16:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ErrorHandler\Handler;


use GuzzleHttp\Psr7\Utils;
use LaborDigital\T3fa\Core\ErrorHandler\Renderer\VerboseHtmlRenderer;
use LaborDigital\T3fa\Core\ErrorHandler\Renderer\VerboseJsonRenderer;
use LaborDigital\T3fa\Core\ErrorHandler\Renderer\VerboseRendererInterface;
use LaborDigital\T3fa\Core\ErrorHandler\UnifiedError;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class VerboseHandler extends AbstractHandler
{
    /**
     * The renderer that is used to render a html content for the error response
     *
     * @var string
     */
    public static $htmlRenderer = VerboseHtmlRenderer::class;
    
    /**
     * The renderer which is used to render a json content for the error response
     *
     * @var string
     */
    public static $jsonRenderer = VerboseJsonRenderer::class;
    
    /**
     * @inheritDoc
     */
    protected function makeResponse(UnifiedError $error): ResponseInterface
    {
        try {
            return $this->getErrorResponse($error)->withBody(
                Utils::streamFor(
                    $this->makeRenderer()->render($error)
                )
            );
        } catch (Throwable $e) {
            return $this
                ->getResponse(500)
                ->withHeader('Content-Type', 'application/vnd.api+json')
                ->withBody(
                    Utils::streamFor(
                        \GuzzleHttp\json_encode(
                            [
                                'errors' => array_merge(
                                    [
                                        [
                                            'code' => $e->getCode(),
                                            'title' => 'While rendering an error, an exception was thrown.',
                                            'meta' => [
                                                'originalMessage' => $e->getMessage(),
                                                'file' => $e->getFile(),
                                                'line' => $e->getLine(),
                                            ],
                                        ],
                                    ],
                                    $error->getStack()
                                ),
                            ],
                            JSON_PRETTY_PRINT
                        )
                    )
                );
        }
        
    }
    
    /**
     * Internal factory to create the correct error renderer instance
     *
     * @return VerboseRendererInterface
     */
    protected function makeRenderer(): VerboseRendererInterface
    {
        if ($this->doesAcceptHttp()) {
            return $this->makeInstance(static::$htmlRenderer);
        }
        
        return $this->makeInstance(static::$jsonRenderer);
    }
    
    
}
