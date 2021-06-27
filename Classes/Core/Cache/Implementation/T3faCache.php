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
 * Last modified: 2021.06.25 at 18:13
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache\Implementation;


use Closure;
use LaborDigital\T3ba\Tool\Cache\Implementation\FrontendCache;
use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Cache\Constraint\ConstraintBuilder;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\Scope\ScopeRegistry;

class T3faCache extends FrontendCache
{
    /**
     * @var \LaborDigital\T3fa\Core\Cache\Constraint\ConstraintBuilder
     */
    protected $constraintBuilder;
    
    /**
     * @var \LaborDigital\T3fa\Core\Cache\Scope\ScopeRegistry
     */
    protected $scopeRegistry;
    
    /**
     * Cache value generator only to track hits with the correct callbacks
     *
     * @var callable
     */
    protected $generator;
    
    /**
     * True if the cache was globally disabled
     *
     * @var bool
     */
    protected $globallyDisabled = false;
    
    /**
     * @inheritDoc
     */
    public function remember(callable $callback, ?array $keyArgs = null, array $options = [])
    {
        $this->generator = $callback;
        
        if ($this->isGloballyDisabled()) {
            $options['enabled'] = false;
        } else {
            $keyArgs = $this->handleQueryParamHash($keyArgs);
        }
        
        try {
            return parent::remember($callback, $keyArgs, $options);
        } finally {
            $this->generator = null;
        }
    }
    
    /**
     * This option allows the outside world to forcefully disable the t3fa cache globally.
     *
     * @param   bool  $state  True to disable the cache, false to reenable it.
     *
     * @return $this
     */
    public function setGloballyDisabled(bool $state = true): self
    {
        $this->globallyDisabled = $state;
        
        return $this;
    }
    
    /**
     * Returns true if the cache was globally disabled, false if not.
     *
     * @return bool
     */
    public function isGloballyDisabled(): bool
    {
        return $this->globallyDisabled;
    }
    
    /**
     * @inheritDoc
     */
    protected function wrapGeneratorCall(string $key, Closure $generator, array $options, array &$tags, ?int &$lifetime, bool &$enabled)
    {
        [$content, $scopeOptions]
            = $this->getScopeRegistry()
                   ->runInScope($this->generator, function () use ($key, $generator, $options, $tags, $lifetime, &$enabled) {
                       /** @var Scope $scope */
                       $scope = $this->getScopeRegistry()->getScope();
                
                       $scope->setCacheLifetime($lifetime);
                       $scope->addCacheTags($tags);
                       $scope->setCacheEnabled($enabled);
                
                       $result = parent::wrapGeneratorCall($key, $generator, $options, $tags, $lifetime, $enabled);
                
                       if (is_callable($options['enabled']) && $scope->isCacheEnabled()) {
                           $scope->setCacheEnabled($enabled);
                       }
                
                       if (is_callable($options['lifetime']) && is_int($lifetime)
                           && ($scope->getCacheLifetime() === null || $scope->getCacheLifetime() > $lifetime)) {
                           $scope->setCacheLifetime($lifetime);
                       }
                
                       $this->getConstraintBuilder()->buildRecordConstraints($scope);
                
                       return $result;
                   }, $key);
        
        $tags = $scopeOptions['tags'];
        $lifetime = (int)$scopeOptions['lifetime'];
        $enabled = $scopeOptions['enabled'];
        
        return $content;
    }
    
    /**
     * @inheritDoc
     */
    protected function beforeWarmup(string $key, $value, array $options)
    {
        $data = parent::beforeWarmup($key, $value, $options);
        
        if (! is_array($data)) {
            return $data;
        }
        
        // We store the tags and the lifetime when we cache a value.
        // This allows us to simulate the same behaviour when a cached value is served like if a new value is served.
        $tags = $data['tags'] ?? [];
        $lifetime = $data['lifetime'] ?? null;
        if (! empty($tags)) {
            $this->runIfScopeExists(static function (Scope $scope) use ($tags, $lifetime) {
                $scope->addCacheTags($tags);
                
                if ($lifetime !== null) {
                    $scope->setCacheLifetime($lifetime);
                }
            });
        }
        
        $this->getScopeRegistry()->getMetricsTracker()->triggerHit(
            $this->generator,
            $key,
            $tags,
            $lifetime,
            $data['generated'] ?? null
        );
        
        return $data['content'] ?? $data;
    }
    
    /**
     * @inheritDoc
     */
    protected function afterFreeze(string $key, $frozen, $value, array $options, array &$tags, ?int &$lifetime)
    {
        return [
            'tags' => $tags,
            'lifetime' => $lifetime,
            'generated' => time(),
            'content' => parent::afterFreeze($key, $frozen, $value, $options, $tags, $lifetime),
        ];
    }
    
    /**
     * Internal helper to find the instance of the scope registry and return it
     *
     * @return \LaborDigital\T3fa\Core\Cache\Scope\ScopeRegistry
     */
    protected function getScopeRegistry(): ScopeRegistry
    {
        if (! isset($this->scopeRegistry)) {
            $this->scopeRegistry = TypoContext::getInstance()->di()->getService(ScopeRegistry::class);
        }
        
        return $this->scopeRegistry;
    }
    
    /**
     * Internal helper to find the instance of the constraint builder and return it
     *
     * @return \LaborDigital\T3fa\Core\Cache\Constraint\ConstraintBuilder
     */
    protected function getConstraintBuilder(): ConstraintBuilder
    {
        if (! isset($this->constraintBuilder)) {
            $this->constraintBuilder = TypoContext::getInstance()->di()->getService(ConstraintBuilder::class);
        }
        
        return $this->constraintBuilder;
    }
    
    /**
     * Internal helper that checks if the @query special key was set in the key args.
     * If the key has a value of TRUE the whole query is taken into account
     * If a string is set as the value only this argument is taken into account
     *
     * @param   null|array  $keyArgs  The list of provided key args
     *
     * @return array
     */
    protected function handleQueryParamHash(?array $keyArgs): ?array
    {
        if (! $keyArgs || ! isset($keyArgs['@query'])) {
            return $keyArgs;
        }
        
        $queryConfig = $keyArgs['@query'];
        unset($keyArgs['@query']);
        
        if (empty($queryConfig)) {
            return $keyArgs;
        }
        
        $request = TypoContext::getInstance()->request()->getRootRequest();
        if (! $request) {
            return $keyArgs;
        }
        
        if ($request->getAttribute('originalRequest') !== null) {
            $request = $request->getAttribute('originalRequest');
        }
        
        $query = $request->getQueryParams();
        if (empty($query)) {
            return $keyArgs;
        }
        
        if ($queryConfig === true) {
            $keyArgs[] = md5(SerializerUtil::serializeJson($query));
        } elseif (isset($query[$queryConfig])) {
            $keyArgs[] = md5(SerializerUtil::serializeJson($query[$queryConfig]));
        }
        
        return $keyArgs;
    }
    
    /**
     * Internal helper that executes the given callback only if a cache scope is currently available
     *
     * @param   callable  $callback
     *
     * @return mixed
     */
    protected function runIfScopeExists(callable $callback)
    {
        $scope = $this->getScopeRegistry()->getScope();
        
        if ($scope !== null) {
            return $callback($scope);
        }
        
        return null;
    }
}