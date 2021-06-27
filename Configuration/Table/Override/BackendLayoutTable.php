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
 * Last modified: 2021.06.02 at 17:37
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Configuration\Table\Override;


use LaborDigital\T3ba\ExtConfig\ExtConfigContext;
use LaborDigital\T3ba\ExtConfigHandler\Table\ConfigureTcaTableInterface;
use LaborDigital\T3ba\ExtConfigHandler\Table\TcaTableNameProviderInterface;
use LaborDigital\T3ba\Tool\Tca\Builder\Type\Table\TcaTable;
use LaborDigital\T3fa\EventHandler\DataHook\BackendLayoutHook;

class BackendLayoutTable implements ConfigureTcaTableInterface, TcaTableNameProviderInterface
{
    /**
     * @inheritDoc
     */
    public static function getTableName(): string
    {
        return 'backend_layout';
    }
    
    /**
     * @inheritDoc
     */
    public static function configureTable(TcaTable $table, ExtConfigContext $context): void
    {
        $table->registerCSHFile('locallang_csh_backend_layout.xlf');
        
        $table->getType(1)->getField('t3fa_identifier')
              ->setLabel('t3fa.t.backendLayout.field.identifier')
              ->moveTo('after:title')
              ->registerSaveHook(BackendLayoutHook::class)
              ->applyPreset()->input();
    }
    
}