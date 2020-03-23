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
 * Last modified: 2020.01.17 at 13:34
 */

namespace LaborDigital\Typo3FrontendApi\HybridApp;


use LaborDigital\Typo3BetterApi\Event\Events\ExtLocalConfLoadedEvent;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractChildExtConfigOption;
use LaborDigital\Typo3FrontendApi\FrontendApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;

class FrontendApiHybridAppOption extends AbstractChildExtConfigOption {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiOption
	 */
	protected $parent;
	
	/**
	 * Holds the raw configuration while we collect the options
	 * @var array
	 */
	protected $config = [
		"enabled"            => FALSE,
		"translationFiles"   => [],
		"globalData"         => [],
		"dataPostProcessors" => [],
		"windowVarName"      => "FRONTEND_API_DATA",
	];
	
	/**
	 * @inheritDoc
	 */
	public function subscribeToEvents(EventSubscriptionInterface $subscription) {
		$subscription->subscribe(ExtLocalConfLoadedEvent::class, "__extLocalConf");
	}
	
	/**
	 * Returns true if the hybrid app mode is enabled.
	 * @return bool
	 */
	public function isHybridModeEnabled(): bool {
		return $this->config["enabled"];
	}
	
	/**
	 * You will use an hybrid app if you are using TYPO3s as your main frontend renderer and just
	 * want to spice up your game with some fancy vue/$yourFrameworkHere widgets on your page.
	 * Hybrid apps are, by design incompatible with server side rendering frameworks like nuxt or similar constructs.
	 * If you want those features you should probably use a site that renders typo3 as an SPA app.
	 *
	 * Hybrid apps can be thought of as multiple instances of your frontend framework; one for each content element
	 * with a shared "global store" and translations which will be injected into the head of the page.
	 *
	 * The data provided by the hybrid api will be made public on window.FRONTEND_API_DATA to your javascript
	 *
	 * @param bool $state True by default to enable the hybrid mode. Can be set to false to disable the hybrid mode
	 *                    later again.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
	 */
	public function useHybridMode(bool $state = TRUE): FrontendApiHybridAppOption {
		if ($this->parent->site()->isSpaModeEnabled())
			throw new FrontendApiException("You can not create a hybrid app on a page that already has sites configured!");
		$this->config["enabled"] = $state;
		return $this;
	}
	
	/**
	 * Sets the available translation files to the given list of files.
	 * All values in the given array should apply to the same rules as the input to addTranslationFile()
	 *
	 * @param array $translationFiles The list of translation files to register
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 */
	public function setTranslationFiles(array $translationFiles): FrontendApiHybridAppOption {
		$this->config["translationFiles"] = [];
		array_map([$this, "addTranslationFile"], $translationFiles);
		return $this;
	}
	
	/**
	 * Adds a new translation file that will be made public for all your hybrid apps/widgets to read.
	 *
	 * @param string $file This can be either a fully qualified path an EXT:... path or just the shortcode of a
	 *                     translation context you registered.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 */
	public function addTranslationFile(string $file): FrontendApiHybridAppOption {
		$file = $this->context->replaceMarkers($file);
		if ($this->context->Translation->hasContext($file))
			$file = $this->context->Translation->getContextFile($file);
		$this->config["translationFiles"][] = $file;
		array_unique($this->config["translationFiles"]);
		return $this;
	}
	
	/**
	 * Removes a previously registered translation file from the list.
	 *
	 * @param string $file This can be either a fully qualified path an EXT:... path or just the shortcode of a
	 *                     translation context you registered.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 */
	public function removeTranslationFile(string $file): FrontendApiHybridAppOption {
		$file = $this->context->replaceMarkers($file);
		if ($this->context->Translation->hasContext($file))
			$file = $this->context->Translation->getContextFile($file);
		$k = array_search($file, $this->config["translationFiles"]);
		if (!is_numeric($k)) return $this;
		unset($this->config["translationFiles"][$k]);
		return $this;
	}
	
	/**
	 * Returns the list of all currently registered translation files that will be provided to the frontend
	 * @return array
	 */
	public function getTranslationFiles(): array {
		return $this->config["translationFiles"];
	}
	
	/**
	 * Sets the global data to the given array. The global data is static, as it will be cached.
	 * To add dynamic data to the global data array you can use a global data processor.
	 *
	 * @param array $data The static, global data to add to your page
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 */
	public function setGlobalData(array $data): FrontendApiHybridAppOption {
		$this->config["globalData"] = $data;
		return $this;
	}
	
	/**
	 * Similar to setGlobalData() but keeps the existing data and simply merges
	 * the given data array into it.
	 *
	 * @param array $data The data array to merge into the existing global data.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 * @see \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption::setGlobalData()
	 */
	public function addToGlobalData(array $data): FrontendApiHybridAppOption {
		$this->config["globalData"] = array_merge($this->config["globalData"], $data);
		return $this;
	}
	
	/**
	 * Returns the data object that will be made publicly available to your hybrid apps/widgets in the frontend.
	 * @return array
	 */
	public function getGlobalData(): array {
		return $this->config["globalData"];
	}
	
	/**
	 * Returns the list of all registered data data post processor classes
	 * @return array
	 */
	public function getGlobalDataProcessors(): array {
		return $this->config["postProcessors"];
	}
	
	/**
	 * Sets the list of global data post processor classes as an array of class names.
	 *
	 * @param array $postProcessors
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 * @see GlobalDataPostProcessorInterface
	 */
	public function setGlobalDataProcessors(array $postProcessors): FrontendApiHybridAppOption {
		$this->config["dataPostProcessors"] = [];
		array_map([$this, "registerGlobalDataPostProcessor"], $postProcessors);
		return $this;
	}
	
	/**
	 * Adds a new global data post processor. They are used to enrich the static data array that was defined by this
	 * class with optional, dynamic content.
	 *
	 * The given class has to implement the GlobalDataPostProcessorInterface interface to work.
	 *
	 * @param string $postProcessor
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 * @see GlobalDataPostProcessorInterface
	 */
	public function registerGlobalDataPostProcessor(string $postProcessor): FrontendApiHybridAppOption {
		if (in_array($postProcessor, $this->config["dataPostProcessors"])) return $this;
		if (!in_array(GlobalDataPostProcessorInterface::class, class_implements($postProcessor)))
			throw new JsonApiException(
				"The given post processor: $postProcessor does not implement the required interface: " .
				GlobalDataPostProcessorInterface::class
			);
		$this->config["dataPostProcessors"][] = $postProcessor;
		return $this;
	}
	
	/**
	 * Removes a previously registered global data post processor from the list.
	 *
	 * @param string $postProcessor
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 */
	public function removeGlobalDataPostProcessor(string $postProcessor): FrontendApiHybridAppOption {
		$k = array_search($postProcessor, $this->config["dataPostProcessors"]);
		if (!is_numeric($k)) return $this;
		unset($this->config["dataPostProcessors"][$k]);
		return $this;
	}
	
	/**
	 * Returns the currently configured window variable name where the global data and the translation
	 * labels will be stored initially.
	 * @return string
	 */
	public function getWindowVarName(): string {
		return $this->config["windowVarName"];
	}
	
	/**
	 * Sets the name of the window variable we use to transfer the global data and translations
	 * to your hybrid apps/widgets. The data will be rendered as json array and is globally available
	 * to the javascript of the frontend. By default the variable is set to: "FRONTEND_API_DATA"
	 *
	 * @param string $varName An alphanumeric variable name that can contain underscores.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\HybridApp\FrontendApiHybridAppOption
	 */
	public function setWindowVarName(string $varName): FrontendApiHybridAppOption {
		$this->config["windowVarName"] = preg_replace("~[^a-zA-Z_0-9]+~si", "", $varName);
		return $this;
	}
	
	/**
	 * Internal helper to fill the main config repository' config array with the local configuration
	 *
	 * @param array $config
	 */
	public function __buildConfig(array &$config): void {
		$config["hybrid"] = $this->config;
	}
	
	/**
	 * Event handler to register the hybrid app data provider if required
	 */
	public function __extLocalConf() {
		// Check if we have a hybrid app, and register the hybrid data provider if required
		if ($this->isHybridModeEnabled() && $this->context->TypoContext->getEnvAspect()->isFrontend())
			$this->context->EventBus->addLazySubscriber(HybridAppDataProvider::class);
	}
}