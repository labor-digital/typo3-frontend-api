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
 * Last modified: 2020.09.26 at 12:57
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Shared;


trait ShortTimeMemoryTrait
{
    /**
     * Stores the remembered values
     *
     * @var array
     */
    protected $memory = [];

    /**
     * Allows your code to remember certain values while the object exists.
     *
     * @param   callable     $generator  Called to generate the value, this is done only once, because the result is remembered after that
     * @param   string|null  $key        An optional key if you want to remember multiple values inside your object
     *
     * @return mixed
     */
    protected function remember(callable $generator, ?string $key = null)
    {
        if ($key === null) {
            $key = 'default';
        }

        if (isset($this->memory[$key])) {
            return $this->memory[$key];
        }

        return $this->memory[$key] = $generator();
    }
}
