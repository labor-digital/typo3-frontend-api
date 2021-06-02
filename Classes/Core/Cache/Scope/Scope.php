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
 * Last modified: 2021.06.01 at 15:50
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache\Scope;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Cache\CacheOptionsTrait;

class Scope implements NoDiInterface
{
    use CacheOptionsTrait {
        getCacheTags as protected getTagsRoot;
    }
    
    /**
     * The parent scope or null if this is the root scope
     *
     * @var \LaborDigital\T3fa\Core\Cache\Scope\Scope|null
     */
    protected $parent;
    
    /**
     * The currently open child scope or null if there is none
     *
     * @var \LaborDigital\T3fa\Core\Cache\Scope\Scope|null
     */
    protected $child;
    
    public function __construct(?Scope $parent)
    {
        $this->parent = $parent;
        
        if ($parent) {
            $parent->child = $this;
        }
    }
    
    /**
     * Returns the list of tags available in this scope
     *
     * @return array
     */
    public function getCacheTags(): array
    {
        return array_unique(array_merge($this->child->cacheTags ?? [], $this->getTagsRoot()));
    }
    
    /**
     * Sets the given lifetime to all currently open scopes.
     * The ttl acts as a max value for all open scopes. All scopes with a lower ttl will be unaffected
     *
     * @param   int|null  $lifetime
     *
     * @return $this
     */
    public function setCacheLifetime(?int $lifetime): self
    {
        $this->setLifetimeInternal($lifetime, true);
        
        return $this;
    }
    
    /**
     * Announces the cache enabled status of the current scope.
     * It only acts if the cache needs to be disabled, which means all currently opened scopes have to be disabled as
     * well
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function setIsCacheEnabled(bool $state): self
    {
        $this->cacheEnabled = $state;
        
        if ($this->parent) {
            $this->parent->setIsCacheEnabled($state);
        }
        
        return $this;
    }
    
    /**
     * INTERNAL method to close the scope - Please only use this if you know exactly what it does!
     *
     * @param   int  $defaultLifetime  The default cache entry lifetime
     *
     * @return array
     * @internal
     */
    protected function close(int $defaultLifetime): array
    {
        if ($this->parent) {
            $this->parent->cacheTags = array_unique(
                array_merge(
                    $this->parent->cacheTags,
                    $this->cacheTags
                )
            );
            $this->parent->child = null;
            $this->parent = null;
        }
        
        if ($this->child) {
            $this->child->close($defaultLifetime);
            $this->child = null;
        }
        
        if (! isset($this->cacheLifetime)) {
            $this->cacheLifetime = $defaultLifetime;
        }
        
        return $this->getCacheOptions();
    }
    
    /**
     * Internal helper to traverse the scope chain upwards to set the ttl for the parent scopes
     *
     * @param   int|null  $lifetime
     * @param   bool      $thisIsMe
     */
    protected function setLifetimeInternal(?int $lifetime, bool $thisIsMe): void
    {
        if ($thisIsMe) {
            $this->cacheLifetime = $lifetime;
        } elseif ($lifetime !== null && ($this->cacheLifetime === null || $this->cacheLifetime > $lifetime)) {
            $this->cacheLifetime = $lifetime;
        }
        
        if ($lifetime === null) {
            return;
        }
        
        if ($this->parent) {
            $this->parent->setLifetimeInternal($lifetime, false);
        }
    }
}
