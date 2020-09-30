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
 * Last modified: 2020.09.30 at 09:32
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu;


use TYPO3\CMS\Frontend\DataProcessing\MenuProcessor;

class ExtendedMenuProcessor extends MenuProcessor
{
    /**
     * @inheritDoc
     * @noinspection UnsupportedStringOffsetOperationsInspection
     */
    public function buildConfiguration()
    {
        parent::buildConfiguration();

        // Rewrite the menu configuration in order to provide the current menu layer to the menu item
        $tMenuKeys = array_keys($this->menuConfig, 'TMENU', true);
        foreach ($tMenuKeys as $TMenuKey) {
            foreach (['NO.', 'ACT.', 'ACTIFSUB.', 'CUR.', 'CURIFSUB.'] as $configKey) {
                if (! is_array($this->menuConfig[$TMenuKey . '.'][$configKey]['stdWrap.']['cObject.'])) {
                    continue;
                }
                $this->menuConfig[$TMenuKey . '.'][$configKey]['stdWrap.']['cObject.'][800]    = 'TEXT';
                $this->menuConfig[$TMenuKey . '.'][$configKey]['stdWrap.']['cObject.']['800.'] = [
                    'value' => (string)$TMenuKey,
                    'wrap'  => ',"level":|',
                ];
            }
        }
    }

}
