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
 * Last modified: 2020.10.28 at 12:19
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache\KeyGeneration;

use LaborDigital\Typo3BetterApi\Tsfe\TsfeService;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;

/**
 * Class EnvironmentCacheKeyGenerator
 *
 * This generator is mostly for internal use, generates a "base" cache key,
 * which is merged with the result of the concrete cache key generator, which was selected in "remember()".
 * This implementation takes the important, current environment into account and generates a unique id from it.
 *
 * @package LaborDigital\Typo3FrontendApi\Cache\KeyGeneration
 */
class EnvironmentCacheKeyGenerator implements CacheKeyGeneratorInterface
{
    /**
     * @var \LaborDigital\Typo3BetterApi\Tsfe\TsfeService
     */
    protected $tsfeService;

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $context;

    public function __construct(TsfeService $tsfeService, TypoContext $context)
    {
        $this->tsfeService = $tsfeService;
        $this->context     = $context;
    }

    /**
     * @inheritDoc
     */
    public function makeCacheKey(): string
    {
        $tsfe = $this->tsfeService->getTsfe();
        $args = [
            $tsfe->type,
            $tsfe->MP,
            implode(',', $this->context->FeUser()->getGroupIds()),
            $this->context->Language()->getCurrentFrontendLanguage()->getTwoLetterIsoCode(),
            $this->context->BeUser()->isLoggedIn(),
            $this->context->Site()->getCurrent()->getRootPageId(),
        ];

        return implode('|', $args);
    }

}
