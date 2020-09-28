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
 * Last modified: 2020.09.25 at 22:19
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache\Scope;


class CacheScope
{
    /**
     * The lifetime of a cache entry in this scope
     *
     * @var int
     */
    public $ttl = 60 * 60 * 24 * 7;

    /**
     * True if the result should be cached, false if not
     *
     * @var bool
     */
    public $enabled = true;

    /**
     * The list of tags that should be associated with the cache entry
     *
     * @var array
     */
    public $tags = [];

    /**
     * The result of the scoped callable
     *
     * @var mixed
     */
    public $result;
}
