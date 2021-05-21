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
 * Last modified: 2019.08.27 at 17:50
 */

namespace LaborDigital\Typo3FrontendApi\Domain\Table\Override;


use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\Table\TableConfigurationInterface;

class TtContentOverrides implements TableConfigurationInterface
{
    public const VIRTUAL_COLUMN_FIELD    = "frontend_api_virtual_columns";
    public const SWITCHABLE_ACTION_FIELD = 'frontend_api_ce_action';
    
    /**
     * @inheritDoc
     */
    public static function configureTable(TcaTable $table, ExtConfigContext $context, bool $isOverride): void
    {
        $table->getField(static::VIRTUAL_COLUMN_FIELD)
              ->applyPreset()->passThrough();
        $table->getField(static::SWITCHABLE_ACTION_FIELD)
              ->setLabel('frontendApi.t.tt_content.ceAction')
              ->applyPreset()
              ->passThrough();
    }
    
}
