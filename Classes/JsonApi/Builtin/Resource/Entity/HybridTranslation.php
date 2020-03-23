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
 * Last modified: 2019.12.10 at 20:10
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;

class HybridTranslation extends AbstractTranslation implements SelfTransformingInterface {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * HybridTranslation constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 */
	public function __construct(FrontendApiConfigRepository $configRepository) {
		$this->configRepository = $configRepository;
	}
	
	/**
	 * @inheritDoc
	 */
	public function asArray(): array {
		return $this->Simulator->runWithEnvironment(["language" => $this->languageId, "fallbackLanguage" => TRUE], function () {
			// Cache the labels for better performance
			$languageKey = $this->TypoContext->getLanguageAspect()->getCurrentFrontendLanguage()->getTwoLetterIsoCode();
			$translationFiles = $this->configRepository->hybridApp()->getTranslationFiles();
			$cacheKey = "hybrid-translation-" . $languageKey . \GuzzleHttp\json_encode($translationFiles);
			if (!$this->GeneralCache->has($cacheKey)) {
				// Build the label list by using the registered files
				$labels = [];
				foreach ($translationFiles as $file) {
					$labelsLocal = $this->Translation->getAllKeysInFile($file);
					$labels = array_merge($labels, $labelsLocal);
				}
				
				// Translate the labels and store them to the cache
				$translations = $this->getLabelTranslations($labels);
				$this->GeneralCache->set($cacheKey, $translations);
			} else {
				// Load translations from cache
				$translations = $this->GeneralCache->get($cacheKey);
			}
			
			// Finish up entity
			return [
				"id"      => $languageKey,
				"message" => $translations,
			];
		});
	}
}