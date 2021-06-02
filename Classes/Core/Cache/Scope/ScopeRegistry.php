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
 * Last modified: 2021.06.01 at 14:37
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache\Scope;


use LaborDigital\T3BA\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Cache\Metrics\MetricsTracker;

class ScopeRegistry implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    /**
     * The current cache scope
     *
     * @var \LaborDigital\T3fa\Core\Cache\Scope\Scope|null
     */
    protected $scope;
    
    /**
     * @var \LaborDigital\T3fa\Core\Cache\Metrics\MetricsTracker
     */
    protected $metricsTracker;
    
    /**
     * The number of seconds how long a cache entry should be valid if nothing else was specified
     *
     * @var int
     */
    protected $defaultLifetime;
    
    /**
     * ScopeRegistry constructor.
     *
     * @param   \LaborDigital\T3fa\Core\Cache\Metrics\MetricsTracker  $metricsTracker
     */
    public function __construct(MetricsTracker $metricsTracker, TypoContext $context)
    {
        $this->metricsTracker = $metricsTracker;
        /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
        $this->defaultLifetime = $context->t3fa()->getConfigValue('site.cache.defaultLifetime', 60 * 60 * 24 * 365);
    }
    
    /**
     * Returns the metrics tracker instance
     *
     * @return \LaborDigital\T3fa\Core\Cache\Metrics\MetricsTracker
     */
    public function getMetricsTracker(): MetricsTracker
    {
        return $this->metricsTracker;
    }
    
    /**
     * Returns either the current scope or null if there is none
     *
     * @return \LaborDigital\T3fa\Core\Cache\Scope\Scope|null
     */
    public function getScope(): ?Scope
    {
        return $this->scope;
    }
    
    /**
     * Runs the given callback inside a cache scope to collect the required caching options
     *
     * @param   callable     $generator
     * @param   callable     $callable
     * @param   string|null  $key
     *
     * @return array Returns an array with two values, 0 => the result of the callable, 1 => an array of cache options gathered by the scope
     */
    public function runInScope(callable $generator, callable $callable, ?string $key): array
    {
        $parentScope = $this->scope;
        
        try {
            $this->scope = $this->makeInstance(Scope::class, [$parentScope]);
            $defaultLifetime = $this->defaultLifetime;
            
            return $this->metricsTracker->recordScope(
                $generator,
                $this->scope,
                $key ?? 'unknown',
                static function (Scope $scope) use ($callable, $defaultLifetime) {
                    return [
                        $callable(),
                        ScopeAdapter::closeScope($scope, $defaultLifetime),
                    ];
                }
            );
            
            
        } finally {
            $this->scope = $parentScope;
        }
    }
    
}
