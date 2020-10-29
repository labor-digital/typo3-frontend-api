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
 * Last modified: 2020.10.28 at 14:11
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache;


use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;

trait CallableReflectionTrait
{
    /**
     * A helper to convert any form of callable into a reflection function object.
     * The $callable be any kind of valid php callable
     *
     * @param $callable
     *
     * @return \ReflectionFunction
     * @throws \LaborDigital\Typo3FrontendApi\Cache\CacheException
     */
    protected function makeReflectionForCallable($callable): ReflectionFunction
    {
        $ref = null;
        if (is_object($callable)) {
            if ($callable instanceof \Closure) {
                $ref = new ReflectionFunction($callable);
            } else {
                $ref = new ReflectionObject((object)$callable);
            }
        }
        if ($ref === null && is_string($callable)) {
            if (class_exists($callable)) {
                $ref = new ReflectionClass($callable);
            } else {
                $ref = new ReflectionFunction($callable);
            }
        }
        if ($ref === null && is_array($callable) && count($callable) === 2) {
            if (is_string($callable[0])) {
                $ref = new ReflectionClass($callable[0]);
            } else {
                $ref = new ReflectionObject($callable[0]);
            }
            $ref = $ref->getMethod($callable[1]);
        }
        if ($ref === null) {
            throw new CacheException('Could not generate a key for your given callable!');
        }

        return $ref;
    }
}
