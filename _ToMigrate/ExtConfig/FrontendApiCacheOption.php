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
 * Last modified: 2020.11.18 at 13:50
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ExtConfig;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigException;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractChildExtConfigOption;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\CacheKeyEnhancerInterface;

class FrontendApiCacheOption extends AbstractChildExtConfigOption
{

    /**
     * The default lifetime in seconds, of a cache entry when nothing other was specified
     *
     * @var int
     */
    protected $defaultTtl = 60 * 60 * 24 * 7;

    /**
     * The used cache key enhancer class.
     *
     * @var null|string
     */
    protected $cacheKeyEnhancerClass;

    /**
     * The identifier of the cache configuration to use
     *
     * @var string
     */
    protected $cacheIdentifier = 't3fa';

    /**
     * Returns the default lifetime in seconds, of a cache entry when nothing other was specified
     *
     * @return int
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * Allows you to update the default lifetime in seconds, of a cache entry when nothing other was specified
     * DEFAULT: 60 * 60 * 24 * 7
     *
     * @param   int  $defaultTtl
     *
     * @return $this
     */
    public function setDefaultTtl(int $defaultTtl): self
    {
        $this->defaultTtl = $defaultTtl;

        return $this;
    }

    /**
     * Returns the used cache key enhancer class.
     *
     * @return string|null
     */
    public function getCacheKeyEnhancerClass(): ?string
    {
        return $this->cacheKeyEnhancerClass;
    }

    /**
     * Allows you to set the used cache key enhancer class.
     *
     * It's basically a "hook" into the environment cache key generator. This key is added to all other cache arguments you add
     * when remember() is executed. In the caching logic some parameters like the language, current frontend groups or similar
     * "global" modifiers are compiled into the cache key to avoid cache overlapping. This hook allows you to extend or modify
     * those modifiers to match your project's needs.
     *
     * The value is the name of the class that is used as a hook and therefore must implement the CacheKeyEnhancerInterface
     *
     * @param   string|null  $cacheKeyEnhancerClass
     *
     * @return $this
     * @throws \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigException
     * @see \LaborDigital\Typo3FrontendApi\Event\EnvironmentCacheKeyFilterEvent
     * @see CacheKeyEnhancerInterface
     */
    public function registerCacheKeyEnhancerClass(?string $cacheKeyEnhancerClass): self
    {
        if (! class_exists($cacheKeyEnhancerClass) || ! in_array(CacheKeyEnhancerInterface::class, class_implements($cacheKeyEnhancerClass), true)) {
            throw new ExtConfigException('The given class ' . $cacheKeyEnhancerClass . ' must implement the interface ' . CacheKeyEnhancerInterface::class);
        }
        $this->cacheKeyEnhancerClass = $cacheKeyEnhancerClass;

        return $this;
    }

    /**
     * Returns the identifier of the cache configuration to use
     *
     * @return string
     */
    public function getCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
    }

    /**
     * Allows you to update the identifier of the cache configuration to use
     *
     * On it's core the caching service is build on top of TYPO3's caching framework.
     * This option defines which cache configuration should be retrieved from the cache manager.
     * You can either modify the cache config of the default "t3fa" cache or completely switch the cache service
     * to another cache implementation using this option.
     *
     * @param   string  $cacheIdentifier
     *
     * @return $this
     */
    public function setCacheIdentifier(string $cacheIdentifier): self
    {
        $this->cacheIdentifier = $cacheIdentifier;

        return $this;
    }

    /**
     * Internal helper to fill the main config repository' config array with the local configuration
     *
     * @param   array  $config
     */
    public function __buildConfig(array &$config): void
    {
        $config['cache'] = [
            'defaultTtl'            => $this->defaultTtl,
            'cacheIdentifier'       => $this->cacheIdentifier,
            'cacheKeyEnhancerClass' => $this->cacheKeyEnhancerClass,
        ];
    }
}
