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
 * Last modified: 2020.09.24 at 12:41
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache\KeyGeneration;


use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RequestCacheKeyGenerator implements CacheKeyGeneratorInterface
{
    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * RequestCacheKeyGenerator constructor.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function makeCacheKey(): string
    {
        $typoContext = GeneralUtility::makeInstance(TypoContext::class);
        $request     = $this->request;

        $params = Arrays::flatten($request->getQueryParams());
        ksort($params);

        return md5(implode('-', [
            $params,
            $request->getMethod(),
            $request->getUri()->getPath(),
            $typoContext->Language()->getCurrentFrontendLanguage()->getTwoLetterIsoCode(),
            $typoContext->Pid()->getCurrent(),
            $typoContext->Site()->getCurrent()->getIdentifier(),
        ]));
    }

}
