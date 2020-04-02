<?php
/**
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
 * Last modified: 2020.04.01 at 13:45
 */

namespace LaborDigital\Typo3FrontendApi\Imaging;


use LaborDigital\Typo3BetterApi\Event\Events\CacheClearedEvent;
use LaborDigital\Typo3BetterApi\Event\Events\TcaCompletelyLoadedEvent;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\Imaging\CodeGeneration\ImagingEndpointGenerator;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;
use Neunerlei\FileSystem\Fs;

class ImagingEventHandler implements LazyEventSubscriberInterface {
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\Imaging\CodeGeneration\ImagingEndpointGenerator
	 */
	protected $endpointGenerator;
	
	/**
	 * ImagingEventHandler constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\TypoContext\TypoContext $context
	 */
	public function __construct(FrontendApiConfigRepository $configRepository, ImagingEndpointGenerator $endpointGenerator) {
		$this->configRepository = $configRepository;
		$this->endpointGenerator = $endpointGenerator;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function subscribeToEvents(EventSubscriptionInterface $subscription) {
		$subscription->subscribe(CacheClearedEvent::class, "__onClear");
		$subscription->subscribe(TcaCompletelyLoadedEvent::class, "__compileEndpoint");
	}
	
	/**
	 * Generates the entry point imaging.php inside the fileadmin directory
	 */
	public function __compileEndpoint() {
		$this->endpointGenerator->generate();
	}
	
	/**
	 * Removes all cached imaging information if the global cache is cleared
	 *
	 * @param \LaborDigital\Typo3BetterApi\Event\Events\CacheClearedEvent $event
	 */
	public function __onClear(CacheClearedEvent $event) {
		if ($event->getGroup() !== "all") return;
		$dir = $this->configRepository->tool()->get("imaging.options.redirectDirectoryPath", "");
		if (is_dir($dir))
			Fs::flushDirectory($dir);
	}
}