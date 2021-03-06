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
 * Last modified: 2021.06.23 at 13:08
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Translation;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3fa\Api\Resource\Entity\TranslationEntity;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class TranslationFactory
{
    use ContainerAwareTrait;
    use T3faCacheAwareTrait;
    
    public function make(SiteLanguage $language, SiteInterface $site): TranslationEntity
    {
        return $this->makeInstance(
            TranslationEntity::class,
            $this->getCache()->remember(
                function () use ($language, $site) {
                    return $this->makeInstance(DataGenerator::class)->generate($language, $site);
                },
                [
                    'translation_resource',
                    $language->getTwoLetterIsoCode(),
                    $site->getIdentifier(),
                ]
            )
        );
    }
}