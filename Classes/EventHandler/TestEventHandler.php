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
 * Last modified: 2021.05.22 at 00:37
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
        
        dbg($repo->getResource('news', 2)->asArray(['includes' => true]));
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
        ])->asArray(['includes' => true]));
        
        $factory = $this->getService(TransformerFactory::class);
        dbge($factory->getTransformer(new PageEntity(1)));
    }
    
}