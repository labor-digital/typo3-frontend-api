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
 * Last modified: 2020.09.24 at 10:55
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache;


trait CacheOptionsTrait
{
    /**
     * Contains the number of seconds for which the cache should be valid.
     * This is null if the default caching duration should be used.
     *
     * @var int|null
     */
    protected $cacheTtl;

    /**
     * True as long as the cache should be enabled.
     *
     * @var bool
     */
    protected $cacheEnabled = true;

    /**
     * Additional tags to be registered for the cache entry
     *
     * @var array
     */
    protected $cacheTags = [];

    /**
     * Returns the number of seconds for which the cache should be valid.
     * This is null if the default caching duration should be used.
     *
     * @return int|null
     */
    public function getCacheTtl(): ?int
    {
        return $this->cacheTtl;
    }

    /**
     * Sets the number of seconds for which the cache should be valid.
     * Can be null if the default caching duration should be used.
     *
     * @param   int|null  $cacheTtl
     *
     * @return $this
     */
    public function setCacheTtl(?int $cacheTtl): self
    {
        $this->cacheTtl = $cacheTtl;

        return $this;
    }

    /**
     * Returns true as long as the cache should be enabled.
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Allows you to define if the cache should be used or not
     *
     * @param   bool  $cacheEnabled
     *
     * @return $this
     */
    public function setCacheEnabled(bool $cacheEnabled): self
    {
        $this->cacheEnabled = $cacheEnabled;

        return $this;
    }

    /**
     * Returns the list of registered cache tags for this entry
     *
     * @return array
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    /**
     * Adds a new tag to the list of cache tags
     *
     * @param   string  $tag
     *
     * @return $this
     */
    public function addCacheTag(string $tag): self
    {
        $this->cacheTags[] = $tag;

        return $this;
    }

    /**
     * Resets the list of cache tags to the given array
     *
     * @param   array  $cacheTags
     *
     * @return $this
     */
    public function setCacheTags(array $cacheTags): self
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * Allows you to set the cache options as an array
     * Note: The "cache" prefix is removed for all properties
     *
     * @param   array  $options
     *
     * @return $this
     */
    public function setCacheOptionsArray(array $options): self
    {
        if (isset($options['ttl'])) {
            $this->setCacheTtl($options['ttl']);
        }
        if (isset($options['enabled'])) {
            $this->setCacheEnabled($options['enabled']);
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
    public function getCacheOptionsArray(): array
    {
        return [
            'ttl'     => $this->cacheTtl,
            'enabled' => $this->cacheEnabled,
            'tags'    => $this->cacheTags,
        ];
    }
}
