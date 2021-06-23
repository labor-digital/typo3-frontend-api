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
 * Last modified: 2021.06.23 at 11:35
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Middleware\Typo;


use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Routing\Util\RedirectUtil;
use LaborDigital\T3fa\Core\Routing\Util\ResponseFactoryTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Redirects\Http\Middleware\RedirectHandler;

class ExtRedirectsMiddleware
{
    public static function registerIfRequired(): ?object
    {
        if (class_exists(RedirectHandler::class)) {
            return new class extends RedirectHandler {
                
                use ResponseFactoryTrait;
                
                public function __construct()
                {
                    $di = TypoContext::getInstance()->di();
                    parent::__construct($di->getService(RedirectHandler::class)->redirectService);
                }
                
                /**
                 * @inheritDoc
                 */
                protected function buildRedirectResponse(UriInterface $uri, array $redirectRecord): ResponseInterface
                {
                    return $this->getJsonResponse(
                        RedirectUtil::makeRedirectData(
                            (string)$uri,
                            null,
                            (int)$redirectRecord['target_statuscode']
                        )
                    );
                }
            };
        }
        
        return null;
    }
}