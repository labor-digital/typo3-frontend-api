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
 * Last modified: 2020.09.21 at 10:30
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache;


use LaborDigital\Typo3BetterApi\NamingConvention\Naming;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\ArrayBasedCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\CacheKeyGeneratorInterface;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\CallableCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\EnvironmentCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\Cache\Metrics\MetricsTracker;
use LaborDigital\Typo3FrontendApi\Cache\Scope\CacheScopeRegistry;
use LaborDigital\Typo3FrontendApi\Cache\Scope\CacheTagAwareInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContext;
use Neunerlei\Options\Options;
use Neunerlei\PathUtil\Path;
use Throwable;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class CacheService implements SingletonInterface
{

    /**
     * @var \LaborDigital\Typo3FrontendApi\Cache\Scope\CacheScopeRegistry
     */
    protected $scopeRegistry;

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    protected $cache;

    /**
     * @var \LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\EnvironmentCacheKeyGenerator
     */
    protected $envCacheKeyGenerator;

    /**
     * @var \LaborDigital\Typo3FrontendApi\Cache\Metrics\MetricsTracker|null
     */
    protected $metricsTracker;

    /**
     * True as long as the cache service is enabled and can be used
     *
     * @var bool
     */
    protected $isEnabled = true;

    /**
     * True if an update of existing values was requested.
     * This means nothing is read from cached entries but the entries are updated using the new value
     *
     * @var bool
     */
    protected $isUpdate = false;


    /**
     * CacheService constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\Cache\Scope\CacheScopeRegistry                    $scopeRegistry
     * @param   \TYPO3\CMS\Core\Cache\CacheManager                                               $cacheManager
     * @param   \LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\EnvironmentCacheKeyGenerator  $envCacheKeyGenerator
     * @param   \LaborDigital\Typo3FrontendApi\Shared\FrontendApiContext                         $context
     */
    public function __construct(
        CacheScopeRegistry $scopeRegistry,
        CacheManager $cacheManager,
        EnvironmentCacheKeyGenerator $envCacheKeyGenerator,
        FrontendApiContext $context
    ) {
        $this->scopeRegistry        = $scopeRegistry;
        $this->cache                = $cacheManager->getCache(
            $context->ConfigRepository()->cache()->get('cacheIdentifier')
        );
        $this->envCacheKeyGenerator = $envCacheKeyGenerator;
    }

    /**
     * Returns true if the cache is currently enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * Allows you to tell the caching service if it is currently enabled or not
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function setEnabled(bool $state): self
    {
        $this->isEnabled = $state;

        return $this;
    }

    /**
     * Returns true if an update of existing values was requested.
     *
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->isUpdate;
    }

    /**
     * Allows you to update existing cache values if set to true.
     * This means nothing is read from cached entries but the entries are updated using the new value
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function setUpdate(bool $state): self
    {
        $this->isUpdate = $state;

        return $this;
    }

    /**
     * Announces a the given cache tag to all currently opened scopes
     *
     * @param   mixed  $tag  The tag to add. This can be a multitude of different types.
     *                       Check stringifyTag() for all allowed types
     *
     * @return $this
     * @see \LaborDigital\Typo3FrontendApi\Cache\CacheService::stringifyTag()
     */
    public function announceTag($tag): self
    {
        foreach ($this->stringifyTag($tag) as $tagString) {
            $this->scopeRegistry->announceTag($tagString);
        }

        return $this;
    }

    /**
     * Announces a list the cache tags to all currently opened scopes
     *
     * @param   array  $tags  The list of tags to add. Check stringifyTag() for all allowed types
     *
     * @return $this
     * @see \LaborDigital\Typo3FrontendApi\Cache\CacheService::stringifyTag()
     */
    public function announceTags(array $tags): self
    {
        foreach ($tags as $tag) {
            $this->announceTag($tag);
        }

        return $this;
    }

    /**
     * Announces the given ttl to all currently open scopes.
     * The ttl acts as a max value for all open scopes. All scopes with a lower ttl will be unaffected
     *
     * @param   int|null  $ttl
     *
     * @return $this
     */
    public function announceTtl(?int $ttl): self
    {
        $this->scopeRegistry->announceTtl($ttl);

        return $this;
    }

    /**
     * Announces the cache enabled status of the current scope.
     * It only acts if the cache needs to be disabled, which means all currently opened scopes have to be disabled as well
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function announceIsEnabled(bool $state): self
    {
        $this->scopeRegistry->announceIsEnabled($state);

        return $this;
    }

    /**
     * Allows you to announce all caching options collected in the CacheOptionTrait at once
     *
     * @param   array  $options  The result of CacheOptionsTrait::getCacheOptionsArray();
     *
     * @return $this
     * @see \LaborDigital\Typo3FrontendApi\Cache\CacheOptionsTrait
     */
    public function announceCacheOptions(array $options): self
    {
        if (isset($options['ttl'])) {
            $this->announceTtl($options['ttl']);
        }
        if (isset($options['enabled'])) {
            $this->announceIsEnabled($options['enabled']);
        }
        if (isset($options['tags'])) {
            $this->announceTags($options['tags']);
        }

        return $this;
    }

    /**
     * Converts the given value into a list of cache tags.
     * see the $tag parameter for all allowed types that can be used as a tag.
     *
     * - Strings and Numbers: Kept as they are
     * - CacheTagAwareInterface: Use the getCacheTag() method
     * - AbstractEntity: Use the matching table name and storage pid
     * - Object: Try to find a getCacheTag() method
     * - Object (alternative): Try to find a getPid() method and combine it with the object class
     * - Object (fallback): try to serialize or json encode the value as an md5
     * - Fallback: If no string tag could be calculated NULL is returned
     *
     * @param   string|int|CacheTagAwareInterface|AbstractEntity|object|null  $tag  The value to convert into a tag.
     *
     * @return array
     */
    public function stringifyTag($tag): array
    {
        if (empty($tag) && $tag !== 0) {
            return [];
        }
        if (is_string($tag) || is_numeric($tag)) {
            return [(string)$tag];
        }

        if (is_object($tag)) {
            $tags = [];
            if ($tag instanceof CacheTagAwareInterface) {
                $tags[] = $tag->getCacheTag();
            } elseif (method_exists($tag, 'getCacheTag')) {
                $tags[] = $tag->getCacheTag();
            }
            if (method_exists($tag, 'getPid')) {
                $tags[] = 'page_' . $tag->getPid();
            }
            if ($tag instanceof AbstractEntity) {
                $tags[] = Naming::resolveTableName($tag) . '_' . $tag->getUid();
            } else {
                $tags[] = Path::classBasename(get_class($tag)) . '_' . md5(get_class($tag));
            }
            if (! empty($tags)) {
                return $tags;
            }
        }

        try {
            return [gettype($tag) . '_' . md5(serialize($tag))];
        } catch (Throwable $e) {
            try {
                return [gettype($tag) . '_' . md5(\GuzzleHttp\json_encode($tag))];
            } catch (Throwable $e) {
                return [];
            }
        }
    }

    /**
     * Flushes all entries of the frontend api cache
     *
     * @return $this
     */
    public function flushCache(): self
    {
        $this->cache->flush();

        return $this;
    }

    /**
     * Flushes all cache entries fo the frontend api cache with a given tag
     *
     * @param   mixed  $tag  The tag to flush from the cache. See stringifyTag() for all allowed values
     *
     * @return $this
     * @see \LaborDigital\Typo3FrontendApi\Cache\CacheService::stringifyTag()
     */
    public function flushCacheForTag($tag): self
    {
        $this->cache->flushByTags($this->stringifyTag($tag));

        return $this;
    }

    /**
     * Allows the outside world to inject a metrics tracker instance
     *
     * @param   \LaborDigital\Typo3FrontendApi\Cache\Metrics\MetricsTracker  $tracker
     */
    public function setMetricsTracker(MetricsTracker $tracker): void
    {
        $this->metricsTracker = $tracker;
    }

    /**
     * The given $callback is called once and then cached. All subsequent calls
     * will then first try to serve the cached value instead of calling $callback again.
     *
     * The execution of remember() can be nested in order to build cached data trees.
     * This also means that outer executions will inherit the cache options like ttl, tags and "enabled" state
     * from the inner executions.
     *
     * @param   callable    $callback  The callable to generate the value to be cached
     * @param   array|null  $keyArgs   Allows you to pass key arguments to generate the cache key with
     *                                 You can omit this parameter if you are supplying your own keyGenerator
     *                                 implementation in the options
     * @param   array       $options   Additional options
     *                                 - ttl int|callable: The numeric value in seconds for how long the cache entry
     *                                 should be stored. Can be a callable which receives the $callback result,
     *                                 to create a ttl based on the output. Is inherited to outer scopes.
     *                                 - enabled bool|callable (true): Allows you to dynamically disable the cache
     *                                 for this execution. Can be a callable which receives the $callback result,
     *                                 to enable/disable the cache based on the output. Is inherited to outer scopes.
     *                                 - keyGenerator CacheKeyGeneratorInterface: The generator instance
     *                                 which is used to generate a cache key for this entry.
     *                                 - tags array: A list of tags that should be added to this cache entry.
     *                                 The tags will be inherited to outer scopes.
     *                                 - onFreeze callable: A callback to execute before the result of $callback is written
     *                                 into the cache. Allows you to perform additional post processing on the fly. The
     *                                 callback receives the result as parameter.
     *                                 - onWarmup callable: A callback to execute when the cached value is read from the caching system.
     *                                 Allows you to rehydrate objects on the fly. The callback receives the value as parameter.
     *
     * @return false|mixed
     */
    public function remember(callable $callback, ?array $keyArgs = null, array $options = [])
    {
        // @todo remove this in v10
        // Legacy adapter if the options are passed as keyArgs
        if (empty($options) && is_array($keyArgs)) {
            foreach (['ttl', 'enabled', 'keyGenerator', 'tags', 'onFreeze', 'onWarmup'] as $optionKey) {
                if (isset($keyArgs[$optionKey])) {
                    $options = $keyArgs;
                    $keyArgs = null;
                    break;
                }
            }
        }

        $options = Options::make($options, [
            'ttl'          => [
                'type'    => ['int', 'null', 'callable'],
                'default' => null,
            ],
            'enabled'      => [
                'type'    => ['bool', 'callable'],
                'default' => true,
            ],
            'keyGenerator' => [
                'type'    => CacheKeyGeneratorInterface::class,
                'default' => static function () use ($callback, $keyArgs) {
                    if (is_array($keyArgs)) {
                        return GeneralUtility::makeInstance(ArrayBasedCacheKeyGenerator::class, $keyArgs);
                    }

                    return GeneralUtility::makeInstance(CallableCacheKeyGenerator::class, $callback);
                },
            ],
            'tags'         => [
                'type'    => 'array',
                'default' => [],
            ],
            'onFreeze'     => [
                'type'    => ['callable', 'null'],
                'default' => null,
            ],
            'onWarmup'     => [
                'type'    => ['callable', 'null'],
                'default' => null,
            ],
        ]);

        // Skip if the caching is disabled
        if (! $this->isEnabled || $options['enabled'] === false) {
            return $callback();
        }

        // Return existing value
        $key = $this->generateCacheKey($options['keyGenerator']);
        if (! $this->isUpdate && $this->cache->has($key)) {
            $result = $this->cache->get($key);
            $tags   = $result['tags'] ?? [];
            $result = $result['content'] ?? $result;

            if ($options['onWarmup'] !== null) {
                $result = call_user_func($options['onWarmup'], $result);
            }

            $this->announceTags($tags);

            if ($this->metricsTracker !== null) {
                $this->metricsTracker->triggerHit($callback, $key, $tags);
            }

            return $result;
        }

        // Create new value
        $runner = function () use ($callback, $options) {
            // Announce the configured tags
            $this->announceTags($options['tags']);

            // Execute the callback
            $result = $callback();

            // Announce the enabled state
            if (is_bool($options['enabled'])) {
                $this->scopeRegistry->announceIsEnabled($options['enabled']);
            } elseif (is_callable($options['enabled'])) {
                $this->scopeRegistry->announceIsEnabled((bool)call_user_func($options['enabled'], $result));
            }

            // Announce the ttl
            if (is_int($options['ttl'])) {
                $this->scopeRegistry->announceTtl($options['ttl']);
            } elseif (is_callable($options['ttl'])) {
                $val = call_user_func($options['ttl'], $result);
                $this->scopeRegistry->announceTtl($val === null ? null : (int)$val);
            }

            return $result;
        };

        // Handle metrics tracker
        if ($this->metricsTracker !== null) {
            $scope = $this->metricsTracker->recordScope(
                $callback, $key,
                function () use ($runner) {
                    return $this->scopeRegistry->runInScope($runner);
                }
            );
        } else {
            $scope = $this->scopeRegistry->runInScope($runner);
        }

        // Skip, if the the caching was disabled on the fly
        if (! $scope->enabled) {
            return $scope->result;
        }

        // Prepare the value to be cached
        $frozen = $scope->result;
        if ($options['onFreeze'] !== null) {
            $frozen = call_user_func($options['onFreeze'], $frozen);
        }

        $frozen = ['content' => $frozen, 'tags' => $scope->tags];
        $this->cache->set($key, $frozen, $scope->tags, $scope->ttl);

        return $scope->result;
    }

    /**
     * Helper to generate a cache key based on the given key generator and the tsfe cache hash
     *
     * @param   \LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\CacheKeyGeneratorInterface  $generator
     *
     * @return string
     */
    protected function generateCacheKey(CacheKeyGeneratorInterface $generator): string
    {
        return md5(implode('.', [
            $generator->makeCacheKey(),
            $this->envCacheKeyGenerator->makeCacheKey(),
        ]));
    }

}
