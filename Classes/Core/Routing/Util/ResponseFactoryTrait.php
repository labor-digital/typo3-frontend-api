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
 * Last modified: 2021.05.31 at 13:53
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
 * Last modified: 2019.08.07 at 08:35
 */

namespace LaborDigital\T3fa\Core\Routing\Util;


use GuzzleHttp\Psr7\Utils;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

trait ResponseFactoryTrait
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $resolvedResponseFactory;
    
    /**
     * Returns the instance of the response factory
     *
     * @return \Psr\Http\Message\ResponseFactoryInterface
     */
    protected function getResponseFactory(): ResponseFactoryInterface
    {
        if (isset($this->resolvedResponseFactory)) {
            return $this->resolvedResponseFactory;
        }
        
        return $this->resolvedResponseFactory = TypoContext::getInstance()->di()->getService(
            ResponseFactoryInterface::class
        );
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
    protected function getResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->getResponseFactory()->createResponse($code, $reasonPhrase);
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
    protected function getJsonResponse(array $data, int $code = 200): ResponseInterface
    {
        $response = $this->getResponse($code);
        $response = $response->withAddedHeader('Content-Type', 'application/json');
        
        return $response->withBody(Utils::streamFor(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)));
    }
    
    /**
     * Returns a simple json response with 'status' => 'OK' as body
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getJsonOkResponse(): ResponseInterface
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
    protected function getJsonApiResponse(array $data, int $code = 200): ResponseInterface
    {
        $response = $this->getResponse($code);
        $response = $response->withAddedHeader('Content-Type', 'application/vnd.api+json');
        
        /** @noinspection JsonEncodingApiUsageInspection */
        return $response->withBody(Utils::streamFor(json_encode($data,
            TypoContext::getInstance()->env()->isFeDebug()
                ? JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
                : JSON_THROW_ON_ERROR
        )));
    }
    
    /**
     * Return a new response with a html data string
     *
     * @param   string  $data
     * @param   int     $code
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getHtmlResponse(string $data, int $code = 200): ResponseInterface
    {
        $response = $this->getResponse($code);
        $response = $response->withHeader('Content-Type', 'text/html');
        
        return $response->withBody(Utils::streamFor($data));
    }
}
