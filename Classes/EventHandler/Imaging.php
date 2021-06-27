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
 * Last modified: 2021.06.24 at 13:47
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\EventHandler;


use LaborDigital\T3ba\Event\Core\CacheClearedEvent;
use LaborDigital\T3fa\Core\Imaging\RequestFactory;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;
use Neunerlei\FileSystem\Fs;

class Imaging implements LazyEventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function subscribeToEvents(EventSubscriptionInterface $subscription): void
    {
        $subscription->subscribe(CacheClearedEvent::class, 'onCacheClear');
    }
    
    /**
     * Flushes the redirect info if all caches are cleared
     *
     * @param   \LaborDigital\T3ba\Event\Core\CacheClearedEvent  $event
     */
    public function onCacheClear(CacheClearedEvent $event): void
    {
        if ($event->getGroup() === 'all') {
            Fs::flushDirectory(RequestFactory::getRedirectInfoStoragePath());
        }
    }
    
}