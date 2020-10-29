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
 * Last modified: 2020.10.28 at 14:10
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Cache\Metrics;

use LaborDigital\Typo3FrontendApi\Cache\CallableReflectionTrait;
use LaborDigital\Typo3FrontendApi\Cache\Scope\CacheScope;

class MetricsTracker
{
    use CallableReflectionTrait;

    /**
     * The list of entries that have been collected
     *
     * @var array
     */
    protected $entries = [];

    /**
     * Adds a new "Hit" into the entry list
     *
     * @param           $callable
     * @param   string  $key
     */
    public function triggerHit($callable, string $key): void
    {
        $this->entries[] = [
            'type'      => 'HIT',
            'key'       => $key,
            'generator' => $this->getCallableId($callable),
        ];
    }

    /**
     * Used to record the child-cache entries inside a cache scope of the cache service
     *
     * @param             $callable
     * @param   string    $key
     * @param   callable  $scopeRunner
     *
     * @return \LaborDigital\Typo3FrontendApi\Cache\Scope\CacheScope
     */
    public function recordScope($callable, string $key, callable $scopeRunner): CacheScope
    {
        $entries       = $this->entries;
        $this->entries = [];
        $scope         = null;
        $time          = microtime(true);
        try {
            return $scope = $scopeRunner();
        } finally {
            $children      = $this->entries;
            $this->entries = $entries;
            if ($scope instanceof CacheScope) {
                $this->entries[] = [
                    'type'      => 'NEW',
                    'key'       => $key,
                    'children'  => $children,
                    'tags'      => $scope->tags,
                    'time'      => microtime(true) - $time,
                    'generator' => $this->getCallableId($callable),
                ];
            } else {
                $this->entries[] = [
                    'type' => 'FAIL',
                    'key'  => $key,
                ];
            }
        }
    }

    /**
     * Returns all collect cache entry metrics
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->entries;
    }

    /**
     * Used to convert a given callable into a readable file and line format
     *
     * @param $callable
     *
     * @return string
     */
    protected function getCallableId($callable): string
    {
        $ref = $this->makeReflectionForCallable($callable);

        return $ref->getFileName() . ' Line: ' . $ref->getStartLine() . '->' . $ref->getEndLine();
    }
}
