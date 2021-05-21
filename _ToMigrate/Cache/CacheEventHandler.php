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
 * Last modified: 2020.09.25 at 23:38
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache;


use LaborDigital\Typo3BetterApi\Event\Events\CacheClearedEvent;
use LaborDigital\Typo3BetterApi\Event\Events\DataHandlerActionPostProcessorEvent;
use LaborDigital\Typo3BetterApi\Event\Events\DataHandlerSavePostProcessorEvent;
use LaborDigital\Typo3BetterApi\Event\Events\ExtBaseAfterPersistObjectEvent;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;

class CacheEventHandler implements LazyEventSubscriberInterface
{
    /**
     * @var \LaborDigital\Typo3FrontendApi\Cache\CacheService
     */
    protected $cacheService;

    /**
     * CacheEventHandler constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\Cache\CacheService  $cacheService
     */
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @inheritDoc
     */
    public static function subscribeToEvents(EventSubscriptionInterface $subscription)
    {
        $subscription->subscribe(CacheClearedEvent::class, 'onCacheCleared');
        $subscription->subscribe([
            DataHandlerActionPostProcessorEvent::class,
            DataHandlerSavePostProcessorEvent::class,
        ], 'onDataHandlerAction');
        $subscription->subscribe(ExtBaseAfterPersistObjectEvent::class, 'onExtBaseObjectPersisting');
    }

    /**
     * Flushes the caches for a single page if the "flash" icon is clicked on a given page
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\CacheClearedEvent  $event
     */
    public function onCacheCleared(CacheClearedEvent $event): void
    {
        if ($event->getGroup() === 'pages' && ! empty($event->getTags())) {
            foreach ($event->getTags() as $tag) {
                // Ignore this tag if itÄs not meant for page caches
                if (stripos($tag, 'pageId_') !== 0) {
                    continue;
                }

                // Skip if this is a cache for a edited content element -> We only want the flash click
                if (in_array('tt_content', $event->getTags(), true)) {
                    break;
                }

                $id = (int)substr($tag, 7);
                $this->cacheService->flushCacheForTag('page_' . $id);
            }
        }
    }

    /**
     * Clears the required cache entries if the data handler modified a record in the database
     *
     * @param   object  $event
     */
    public function onDataHandlerAction(object $event): void
    {
        $table = $event->getTableName();
        $id    = $event->getId();
        if (! is_numeric($id)) {
            return;
        }
        $this->cacheService->flushCacheForTag($table . '_' . $id);
    }

    /**
     * Clears the required cache entries if extbase persisted an object
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\ExtBaseAfterPersistObjectEvent  $event
     */
    public function onExtBaseObjectPersisting(ExtBaseAfterPersistObjectEvent $event): void
    {
        $this->cacheService->flushCacheForTag($event->getObject());
    }
}
