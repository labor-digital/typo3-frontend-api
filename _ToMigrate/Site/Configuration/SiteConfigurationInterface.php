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
 * Last modified: 2019.09.19 at 10:00
 */

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;

use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;

interface SiteConfigurationInterface
{

    /**
     * Receives the site configurator and should provide the required information for the frontend site
     *
     * @param   \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator  $configurator
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext             $context
     *
     * @return void
     */
    public static function configureSite(SiteConfigurator $configurator, ExtConfigContext $context);

}
