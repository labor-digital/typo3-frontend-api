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
 * Last modified: 2021.06.04 at 21:34
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache;


use LaborDigital\T3ba\Tool\Cache\Util\CacheUtil;

trait CacheOptionsTrait
{
    
    /**
     * Contains the number of seconds for which the cache should be valid.
     * This is null if the default caching duration should be used.
     *
     * @var int|null
     */
    protected $cacheLifetime;
    
    /**
     * True if the result should be cached, false if not
     *
     * @var bool
     */
    protected $cacheEnabled = true;
    
    /**
     * The list of tags that should be associated with the cache entry
     *
     * @var array
     */
    protected $cacheTags = [];
    
    /**
     * Adds a the given cache tag to all currently opened scopes
     *
     * @param   mixed  $tag  The tag to add. This can be a multitude of different types.
     *                       Check stringifyTag() for all allowed types
     *
     * @return $this
     * @see \LaborDigital\T3ba\Tool\Cache\Util\CacheUtil::stringifyTag()
     */
    public function addCacheTag($tag)
    {
        $this->cacheTags = array_unique(
            array_merge($this->cacheTags, CacheUtil::stringifyTag($tag))
        );
        
        return $this;
    }
    
    /**
     * Exactly the same as "addTag" but accepts multiple tags at once
     *
     * @param   array  $tags
     *
     * @return $this
     */
    public function addCacheTags(array $tags)
    {
        foreach ($tags as $tag) {
            $this->addCacheTag($tag);
        }
        
        return $this;
    }
    
    /**
     * Sets a list the cache tags for this and the parent scope
     *
     * @param   array  $tags  The list of tags to add. Check stringifyTag() for all allowed types
     *
     * @return $this
     * @see \LaborDigital\T3ba\Tool\Cache\Util\CacheUtil::stringifyTag()
     */
    public function setCacheTags(array $tags)
    {
        $this->cacheTags = [];
        
        foreach ($tags as $tag) {
            $this->addCacheTag($tag);
        }
        
        return $this;
    }
    
    /**
     * Returns the list of tags available in this scope
     *
     * @return array
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }
    
    /**
     * Sets the given lifetime to all currently open scopes.
     * The ttl acts as a max value for all open scopes. All scopes with a lower ttl will be unaffected
     *
     * @param   int|null  $lifetime
     *
     * @return $this
     */
    public function setCacheLifetime(?int $lifetime)
    {
        $this->cacheLifetime = $lifetime;
        
        return $this;
    }
    
    /**
     * Returns the minimum ttl of this and all child scopes that is currently set.
     *
     * @return int|null
     */
    public function getCacheLifetime(): ?int
    {
        return $this->cacheLifetime;
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
    public function setIsCacheEnabled(bool $state)
    {
        $this->cacheEnabled = $state;
        
        return $this;
    }
    
    /**
     * Returns true if this cache is enabled, false if not
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }
    
    /**
     * Allows you to announce all caching options collected in the CacheOptionTrait at once
     *
     * @param   array  $options  The result of CacheOptionsTrait::getCacheOptionsArray();
     *
     * @return $this
     * @see \LaborDigital\T3fa\Core\Cache\CacheOptionsTrait
     */
    public function setCacheOptions(array $options)
    {
        if (isset($options['lifetime'])) {
            $this->setCacheLifetime($options['lifetime']);
        }
        
        if (isset($options['enabled'])) {
            $this->setIsCacheEnabled($options['enabled']);
        }
        
        if (isset($options['tags'])) {
            $this->setCacheTags($options['tags']);
        }
        
        return $this;
    }
    
    /**
     * Returns the array of configured cache options
     *
     * @return array
     */
    public function getCacheOptions(): array
    {
        return [
            'lifetime' => $this->cacheLifetime,
            'enabled' => $this->cacheEnabled,
            'tags' => $this->cacheTags,
        ];
    }
}
