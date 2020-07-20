<?php
declare(strict_types=1);
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
 * Last modified: 2019.09.20 at 14:12
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Cache\GeneralCache;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\SiteConfigAwareTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;

/**
 * Class PageTranslation
 *
 * @package LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Model
 */
class PageTranslation extends AbstractTranslation implements SelfTransformingInterface
{
    use SiteConfigAwareTrait;
    
    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return $this->Simulator()->runWithEnvironment(['language' => $this->languageId, 'fallbackLanguage' => true], function () {
            $siteConfig = $this->getCurrentSiteConfig();
            
            // Cache the labels for better performance
            $languageKey = $this->TypoContext()->Language()->getCurrentFrontendLanguage()->getTwoLetterIsoCode();
            $cacheKey    = 'page-translation-' . $languageKey . $siteConfig->siteIdentifier .
                           \GuzzleHttp\json_encode($siteConfig->translationLabels);
            if (! $this->getSingletonOf(GeneralCache::class)->has($cacheKey)) {
                $translations = $this->getLabelTranslations($siteConfig->translationLabels);
                $this->getSingletonOf(GeneralCache::class)->set($cacheKey, $translations);
            } else {
                // Load translations from cache
                $translations = $this->getSingletonOf(GeneralCache::class)->get($cacheKey);
            }
            
            // Finish up entity
            return [
                'id'      => $languageKey,
                'message' => $translations,
            ];
        });
    }
    
}
