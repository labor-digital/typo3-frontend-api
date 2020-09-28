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
 * Last modified: 2020.07.21 at 13:01
 */

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

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Translation;


use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\ArrayBasedCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;

/**
 * Class PageTranslation
 *
 * @package LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Model
 */
class PageTranslation extends AbstractTranslation implements SelfTransformingInterface
{

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $context    = $this->FrontendApiContext();
        $siteConfig = $context->getCurrentSiteConfig();

        return $context->CacheService()->remember(function () use ($context, $siteConfig) {
            return $context->Simulator()->runWithEnvironment(
                ['language' => $this->languageId, 'fallbackLanguage' => true],
                function () use ($context, $siteConfig) {
                    return [
                        'id'      => $context->getLanguageCode(),
                        'message' => $this->getLabelTranslations($siteConfig->translationLabels),
                    ];
                }
            );
        }, [
            'tags'         => ['translation'],
            'keyGenerator' => $context->getInstanceWithoutDi(ArrayBasedCacheKeyGenerator::class, [
                [[__CLASS__, $this->languageId, $siteConfig->translationLabels]],
            ]),
        ]);
    }

}
