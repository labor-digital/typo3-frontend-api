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
 * Last modified: 2021.06.21 at 20:34
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Configuration\ExtConfig;


use LaborDigital\T3ba\ExtConfig\ExtConfigContext;
use LaborDigital\T3ba\ExtConfigHandler\Core\ConfigureTypoCoreInterface;
use LaborDigital\T3ba\ExtConfigHandler\Core\TypoCoreConfigurator;
use LaborDigital\T3ba\ExtConfigHandler\Raw\ConfigureRawSettingsInterface;
use LaborDigital\T3ba\ExtConfigHandler\Translation\ConfigureTranslationInterface;
use LaborDigital\T3ba\ExtConfigHandler\Translation\TranslationConfigurator;
use LaborDigital\T3fa\Api\Resource\Factory\ContentElement\ContentObject\ThrowingRecordsContentObject;
use LaborDigital\T3fa\Core\Cache\Backend\EntryLimitedTypo3DatabaseBackend;
use Neunerlei\Configuration\State\ConfigState;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

class Common implements ConfigureTypoCoreInterface, ConfigureTranslationInterface, ConfigureRawSettingsInterface
{
    /**
     * @inheritDoc
     */
    public static function configureCore(TypoCoreConfigurator $configurator, ExtConfigContext $context): void
    {
        // Register the cache for our implementation
        $configurator
            ->registerCache('t3fa_frontend',
                VariableFrontend::class,
                EntryLimitedTypo3DatabaseBackend::class,
                [
                    'options' => [
                        'compression' => true,
                        'defaultLifetime' => 0,
                    ],
                    'groups' => 'pages',
                ]
            );
    }
    
    /**
     * @inheritDoc
     */
    public static function configureRaw(ConfigState $state, ExtConfigContext $context): void
    {
        // Register globals configuration for the TYPO3 core api
        $state->mergeIntoArray('typo.globals.TYPO3_CONF_VARS', [
            'FE' => [
                'ContentObjects' => [
                    'T3FA_RECORDS_THROWING' => ThrowingRecordsContentObject::class,
                ],
            ],
        ]);
    }
    
    
    /**
     * @inheritDoc
     */
    public static function configureTranslation(TranslationConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->registerNamespace('t3fa');
    }
    
}