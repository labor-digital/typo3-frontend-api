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
 * Last modified: 2021.05.28 at 20:27
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\EventHandler;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Event\Frontend\FrontendAssetFilterEvent;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerFactory;
use LaborDigital\T3fa\Resource\Entity\PageEntity;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;

class TestEventHandler implements LazyEventSubscriberInterface
{
    use ContainerAwareTrait;
    use TypoContextAwareTrait;
    
    /**
     * @inheritDoc
     */
    public static function subscribeToEvents(EventSubscriptionInterface $subscription): void
    {
        $subscription->subscribe(FrontendAssetFilterEvent::class, 'run');
    }
    
    public function run()
    {
        $repo = $this->getService(ResourceRepository::class);
        
        dbge($repo->getResource('page', '/job-finden/job/45/asdf'));
//        dbge($repo->getCollection('proxy', ['filter' => ['title' => 'hello'], 'page' => ['number' => 2]])->asArray());
        dbg($repo->getResource('proxy', 2)->asArray(['include' => true]));
        dbge($repo->getCollection('news', [
            'filter' => [
                'dateRange' => [
                    'start' => '2022-04',
                    'end' => '2022-06',
                ],
            ],
            'page' => [
                'size' => 0,
                'number' => 1,
            ],
        ])->asArray(['include' => true]));
        
        $factory = $this->getService(TransformerFactory::class);
        dbge($factory->getTransformer(new PageEntity(1)));
    }
    
}