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
 * Last modified: 2021.06.01 at 13:43
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache;


use LaborDigital\T3ba\Tool\Cache\CacheFactory;
use LaborDigital\T3ba\Tool\Cache\CacheInterface;
use LaborDigital\T3fa\Core\Cache\Implementation\T3faCache;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\Scope\ScopeRegistry;

trait T3faCacheAwareTrait
{
    /**
     * @var \LaborDigital\T3ba\Tool\Cache\CacheInterface
     */
    protected $t3faCache;
    
    /**
     * @var \LaborDigital\T3fa\Core\Cache\Scope\ScopeRegistry
     */
    protected $t3faCacheScopeRegistry;
    
    public function injectT3faCache(CacheFactory $cacheFactory, ScopeRegistry $registry): void
    {
        $this->t3faCache = $cacheFactory->makeCacheImplementation(
            T3faCache::class, 't3fa_frontend'
        );
        $this->t3faCacheScopeRegistry = $registry;
    }
    
    /**
     * Returns the t3fa cache instance
     *
     * @return \LaborDigital\T3ba\Tool\Cache\CacheInterface
     */
    protected function getCache(): CacheInterface
    {
        return $this->t3faCache;
    }
    
    /**
     * Returns the current cache scope/the slice of currently active cache options
     * It alternatively returns null if there is currently no cache scope active
     *
     * @return \LaborDigital\T3fa\Core\Cache\Scope\Scope|null
     */
    protected function getCacheScope(): ?Scope
    {
        return $this->t3faCacheScopeRegistry->getScope();
    }
    
    /**
     * A simple helper that executes the given callback only if a cache scope is currently available
     *
     * @param   callable  $callback  The callback to execute, it receives the current $scope as argument
     */
    protected function runInCacheScope(callable $callback): void
    {
        $scope = $this->getCacheScope();
        if ($scope) {
            $callback($scope);
        }
    }
}