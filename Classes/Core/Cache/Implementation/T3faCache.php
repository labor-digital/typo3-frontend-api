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
 * Last modified: 2021.06.01 at 15:49
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache\Implementation;


use Closure;
use LaborDigital\T3ba\Tool\Cache\Implementation\FrontendCache;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\Scope\ScopeRegistry;

class T3faCache extends FrontendCache
{
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
     * @inheritDoc
     */
    public function remember(callable $callback, ?array $keyArgs = null, array $options = [])
    {
        $this->generator = $callback;
        
        try {
            return parent::remember($callback, $keyArgs, $options);
        } finally {
            $this->generator = null;
        }
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
                       $scope->setIsCacheEnabled($enabled);
                
                       $result = parent::wrapGeneratorCall($key, $generator, $options, $tags, $lifetime, $enabled);
                
                       if (is_callable($options['enabled'])) {
                           $scope->setIsCacheEnabled($enabled);
                       }
                
                       if (is_callable($options['lifetime']) && is_int($lifetime)
                           && ($scope->getCacheLifetime() === null || $scope->getCacheLifetime() > $lifetime)) {
                           $scope->setCacheLifetime($lifetime);
                       }
                
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