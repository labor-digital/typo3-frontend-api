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
 * Last modified: 2019.08.07 at 08:35
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Traits;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;

trait ResponseFactoryTrait
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * Injects the response factory
     *
     * @param   \Psr\Http\Message\ResponseFactoryInterface  $factory
     */
    public function injectResponseFactory(ResponseFactoryInterface $factory): void
    {
        $this->responseFactory = $factory;
    }

    /**
     * Create a new response.
     *
     * @param   int     $code          HTTP status code; defaults to 200
     * @param   string  $reasonPhrase  Reason phrase to associate with status code
     *                                 in generated response; if none is provided implementations MAY use
     *                                 the defaults as suggested in the HTTP specification.
     *
     * @return ResponseInterface
     */
    public function getResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (! isset($this->responseFactory)) {
            $this->responseFactory = TypoContainer::getInstance()->get(ResponseFactoryInterface::class);
        }

        return $this->responseFactory->createResponse($code, $reasonPhrase);
    }

    /**
     * Create a new response with json data
     *
     * @param   array  $data
     * @param   int    $code
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \JsonException
     */
    public function getJsonResponse(array $data, int $code = 200): ResponseInterface
    {
        $response = $this->getResponse($code);
        $response = $response->withAddedHeader('Content-Type', 'application/json');

        return $response->withBody(stream_for(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)));
    }

    /**
     * Returns a simple json response with 'status' => 'OK' as body
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getJsonOkResponse(): ResponseInterface
    {
        return $this->getJsonResponse(['status' => 'OK']);
    }

    /**
     * Create a new response with json api data
     *
     * @param   array  $data
     * @param   int    $code
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \JsonException
     */
    public function getJsonApiResponse(array $data, int $code = 200): ResponseInterface
    {
        $response = $this->getResponse($code);
        $response = $response->withAddedHeader('Content-Type', 'application/vnd.api+json');

        return $response->withBody(stream_for(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)));
    }

    /**
     * Return a new response with a html data string
     *
     * @param   string  $data
     * @param   int     $code
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getHtmlResponse(string $data, int $code = 200): ResponseInterface
    {
        $response = $this->getResponse($code);
        $response = $response->withHeader('Content-Type', 'text/html');

        return $response->withBody(stream_for($data));
    }
}
