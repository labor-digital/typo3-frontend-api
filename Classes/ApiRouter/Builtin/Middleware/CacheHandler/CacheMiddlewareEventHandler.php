<?php
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.12.06 at 13:45
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\CacheHandler;


use LaborDigital\Typo3BetterApi\Cache\FrontendCache;
use LaborDigital\Typo3BetterApi\Event\Events\DataHandlerActionPostProcessorEvent;
use LaborDigital\Typo3BetterApi\Event\Events\DataHandlerSavePostProcessorEvent;
use LaborDigital\Typo3BetterApi\Event\Events\ExtBaseAfterPersistObjectEvent;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;

/**
 * Class CacheMiddlewareEventHandler
 *
 * This event handler is used to listen on any kind of database transaction (at least those we can listen to)
 * and automatically deletes the api result caches if anything in the database changes.
 *
 * This is quite brutal, but there is no real better solution as there are so many cross dependencies
 * between content elements (initial state) and record tables.
 *
 * @package LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\CacheHandler
 */
class CacheMiddlewareEventHandler implements LazyEventSubscriberInterface {
	
	/**
	 * This is true if the cache was already flushed in this lifecycle,
	 * so we don't have to do it again
	 * @var bool
	 */
	protected static $cacheFlushed = FALSE;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Cache\FrontendCache
	 */
	protected $frontendCache;
	
	/**
	 * CacheMiddlewareEventHandler constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\Cache\FrontendCache $frontendCache
	 */
	public function __construct(FrontendCache $frontendCache) {
		$this->frontendCache = $frontendCache;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function subscribeToEvents(EventSubscriptionInterface $subscription) {
		$subscription->subscribe([
			DataHandlerActionPostProcessorEvent::class,
			DataHandlerSavePostProcessorEvent::class,
			ExtBaseAfterPersistObjectEvent::class,
		], "__onCacheClearEvent");
	}
	
	/**
	 * Event handler to flush the caches when a database action is performed
	 *
	 * @param object $e
	 */
	public function __onCacheClearEvent(object $e) {
		// Ignore if the cache was already flushed
		if (static::$cacheFlushed) return;
		
		// Check if we can determine a table name
		if ($e instanceof DataHandlerSavePostProcessorEvent || $e instanceof DataHandlerActionPostProcessorEvent) {
			$table = $e->getTableName();
			// Ignore all "cf_", "be_", "fe_" tables
			if (in_array(substr($table, 0, 3), ["cf_", "be_", "fe_"])) return;
			// Ignore all but some "sys_" tables
			if (substr($table, 0, 4) === "sys_" && !in_array($table, [
					"sys_file", "sys_file_reference", "sys_category", "sys_registry",
				])) return;
			// Ignore some static tables
			if (in_array($table, [
				"backend_layout", "cache_treelist",
			])) return;
		}
		
		// Mark the caches as flushed
		static::$cacheFlushed = TRUE;
		
		// Flush the cache
		$this->frontendCache->clearTags([CacheMiddleware::CACHE_TAG]);
	}
}