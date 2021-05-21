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
 * Last modified: 2019.12.10 at 19:55
 */

namespace LaborDigital\Typo3FrontendApi\HybridApp;


use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3BetterApi\Event\Events\FrontendAssetPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\HybridApiDataPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\FrontendApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Translation\HybridTranslation;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;

/**
 * Class HybridAppDataProvider
 *
 * This class is used to inject the hybrid app data object into the header of the typo3 html
 *
 * @package LaborDigital\Typo3FrontendApi\HybridApp
 */
class HybridAppDataProvider implements LazyEventSubscriberInterface
{
    use CommonServiceLocatorTrait;

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
     */
    protected $transformerFactory;

    /**
     * @inheritDoc
     */
    public static function subscribeToEvents(EventSubscriptionInterface $subscription)
    {
        $subscription->subscribe(FrontendAssetPostProcessorEvent::class, "__inject");
    }

    /**
     * HybridAppDataProvider constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository      $configRepository
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory  $transformerFactory
     */
    public function __construct(FrontendApiConfigRepository $configRepository, TransformerFactory $transformerFactory)
    {
        $this->configRepository   = $configRepository;
        $this->transformerFactory = $transformerFactory;
    }

    /**
     * The event listener that injects the global, hybrid data
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\FrontendAssetPostProcessorEvent  $event
     *
     * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
     */
    public function __inject(FrontendAssetPostProcessorEvent $event)
    {
        $assets          = $event->getAssets();
        $hybridAppConfig = $this->configRepository->hybridApp();

        // Prepare the data by the post processors
        $data = $hybridAppConfig->getGlobalData();
        foreach ($hybridAppConfig->getGlobalDataPostProcessors() as $postProcessor) {
            if (! class_exists($postProcessor) || ! in_array(GlobalDataPostProcessorInterface::class, class_implements($postProcessor))) {
                throw new FrontendApiException("Could not execute the global data post processor class: $postProcessor, as it either not exists, " .
                                               "or does not implement the required" . GlobalDataPostProcessorInterface::class . " interface!");
            }
            $data = $this->getInstanceOf($postProcessor)->process($data);
        }

        // Transform the value like any other json api response, but remove the id
        $value = $this->transformerFactory->getTransformer()->transform([
            "id"           => -1,
            "translations" => $this->getInstanceOf(HybridTranslation::class, [
                $this->TypoContext->getLanguageAspect()->getCurrentFrontendLanguage()->getLanguageId(),
            ]),
            "data"         => $hybridAppConfig->getGlobalData(),
        ]);
        unset($value["id"]);

        // Allow filtering
        $windowVar = $hybridAppConfig->getWindowVarName();
        $this->EventBus->dispatch(($e = new HybridApiDataPostProcessorEvent($windowVar, $value, $event)));
        $windowVar = $e->getWindowVar();
        $value     = $e->getData();

        // Build the tag
        $content = "window." . $windowVar . "=" . \GuzzleHttp\json_encode($value);
        $tag     = "<script type=\"text/javascript\">$content</script>";

        // Inject the tag
        $assets["jsInline"] = $tag . $assets["jsInline"];
        $event->setAssets($assets);
    }
}
