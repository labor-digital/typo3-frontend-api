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
 * Last modified: 2020.09.24 at 12:18
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache\KeyGeneration;


use LaborDigital\Typo3FrontendApi\Cache\CallableReflectionTrait;

class CallableCacheKeyGenerator implements CacheKeyGeneratorInterface
{
    use CallableReflectionTrait;

    /**
     * @var callable
     */
    protected $callable;

    /**
     * CallableCacheKeyGenerator constructor.
     *
     * @param   callable  $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @inheritDoc
     */
    public function makeCacheKey(): string
    {
        $ref = $this->makeReflectionForCallable($this->callable);

        return md5($ref->getFileName() . '_' . $ref->getStartLine() . '_' . $ref->getEndLine());
    }
}
