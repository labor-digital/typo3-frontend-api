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
 * Last modified: 2021.06.01 at 13:39
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Core\Cache\Metrics;

use LaborDigital\T3ba\Tool\OddsAndEnds\ReflectionUtil;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;

class MetricsTracker
{
    
    /**
     * The list of entries that have been collected
     *
     * @var array
     */
    protected $entries = [];
    
    /**
     * @inheritDoc
     */
    public function triggerHit($callable, string $key, array $tags, ?int $lifetime, ?int $generated): void
    {
        $this->entries[] = [
            'type' => 'HIT',
            'key' => $key,
            'generator' => $this->getCallableId($callable),
            'tags' => $tags,
            'lifetime' => $lifetime,
            'generated' => $generated,
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function recordScope(callable $generator, Scope $scope, string $key, callable $scopeRunner): array
    {
        $entries = $this->entries;
        $this->entries = [];
        $time = microtime(true);
        try {
            return $scopeRunner($scope);
        } finally {
            $children = $this->entries;
            $this->entries = $entries;
            
            $this->entries[] = [
                'type' => $scope->isCacheEnabled() ? 'NEW' : 'NO_CACHE',
                'key' => $key,
                'children' => $children,
                'lifetime' => $scope->getCacheLifetime(),
                'tags' => $scope->getCacheTags(),
                'time' => microtime(true) - $time,
                'generated' => time(),
                'generator' => $this->getCallableId($generator),
            ];
        }
    }
    
    /**
     * Returns the list of all tracked cache scopes
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
        $ref = ReflectionUtil::makeReflectionForCallable($callable);
        
        return $ref->getFileName() . ' Line: ' . $ref->getStartLine() . '->' . $ref->getEndLine();
    }
}
