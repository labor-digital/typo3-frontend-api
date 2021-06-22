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
 * Last modified: 2021.06.21 at 16:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\LayoutObject\Renderer\Menu;


use TYPO3\CMS\Frontend\DataProcessing\MenuProcessor;

class ExtendedMenuProcessor extends MenuProcessor
{
    /**
     * Allows external scripts to offset the level counter by a certain margin
     *
     * @var int
     */
    public static $levelOffset = 0;
    
    /**
     * @inheritDoc
     * @noinspection UnsupportedStringOffsetOperationsInspection
     */
    public function buildConfiguration(): void
    {
        parent::buildConfiguration();
        
        // Rewrite the menu configuration in order to provide the current menu layer to the menu item
        foreach (array_keys($this->menuConfig, 'TMENU', true) as $TMenuKey) {
            foreach (['NO.', 'SPC.', 'ACT.', 'IFSUB.', 'ACTIFSUB.', 'CUR.', 'CURIFSUB.'] as $configKey) {
                if (! isset($this->menuConfig[$TMenuKey . '.'][$configKey])) {
                    continue;
                }
                
                if (! is_array($this->menuConfig[$TMenuKey . '.'][$configKey]['stdWrap.']['cObject.'])) {
                    continue;
                }
                
                $this->menuConfig[$TMenuKey . '.'][$configKey]['stdWrap.']['cObject.'][800] = 'TEXT';
                $this->menuConfig[$TMenuKey . '.'][$configKey]['stdWrap.']['cObject.']['800.'] = [
                    'value' => (string)((int)$TMenuKey + (int)static::$levelOffset),
                    'wrap' => ',"level":|',
                ];
            }
        }
    }
    
}
