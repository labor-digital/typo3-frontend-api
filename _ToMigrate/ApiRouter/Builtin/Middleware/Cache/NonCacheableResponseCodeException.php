<?php
/*
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.09.24 at 12:45
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\Cache;


use LaborDigital\Typo3FrontendApi\Cache\CacheException;
use Psr\Http\Message\ResponseInterface;

class NonCacheableResponseCodeException extends CacheException
{

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Creates a new instance of myself
     *
     * @param   \Psr\Http\Message\ResponseInterface  $response
     *
     * @return static
     */
    public static function makeNew(ResponseInterface $response): self
    {
        $i           = new self($response->getReasonPhrase(), $response->getStatusCode());
        $i->response = $response;

        return $i;
    }
}
