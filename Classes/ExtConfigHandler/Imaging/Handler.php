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
 * Last modified: 2021.06.24 at 14:09
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Imaging;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractSimpleExtConfigHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;

class Handler extends AbstractSimpleExtConfigHandler
{
    protected $configureMethod = 'configureImagingApi';
    
    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
        $this->registerDefaultLocation($configurator);
        $configurator->registerInterface(ConfigureImagingApiInterface::class);
        $configurator->registerDefaultState([
            't3fa' => [
                'imaging' => [
                    'enabled' => getenv('T3FA_IMAGING_DISABLED') === false,
                ],
            ],
        ]);
    }
    
    /**
     * @inheritDoc
     */
    protected function getConfiguratorClass(): string
    {
        return ImagingConfigurator::class;
    }
    
    /**
     * @inheritDoc
     */
    protected function getStateNamespace(): string
    {
        return 't3fa.imaging';
    }
    
}