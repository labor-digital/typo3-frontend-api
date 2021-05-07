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
 * Last modified: 2021.05.07 at 11:17
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Shared\Hydrator;


use LaborDigital\Typo3BetterApi\NotImplementedException;
use TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager;

class ConcreteConfigManagerAdapter extends AbstractConfigurationManager
{
    public static function flushCache(AbstractConfigurationManager $configurationManager): void
    {
        $configurationManager->configurationCache = [];
    }

    /**
     * @internal
     * @hidden
     */
    public function getTypoScriptSetup()
    {
        throw new NotImplementedException();
    }

    /**
     * @internal
     * @hidden
     */
    protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration)
    {
        throw new NotImplementedException();
    }

    /**
     * @internal
     * @hidden
     */
    protected function getPluginConfiguration($extensionName, $pluginName = null)
    {
        throw new NotImplementedException();
    }

    /**
     * @internal
     * @hidden
     */
    protected function getSwitchableControllerActions($extensionName, $pluginName)
    {
        throw new NotImplementedException();
    }

    /**
     * @internal
     * @hidden
     */
    protected function getRecursiveStoragePids($storagePid, $recursionDepth = 0)
    {
        throw new NotImplementedException();
    }
}
