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
 * Last modified: 2021.06.21 at 13:51
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\LayoutObject\Renderer;


use LaborDigital\T3fa\Core\LayoutObject\Renderer\Menu\AbstractMenuRenderer;

class RootLineMenuRenderer extends AbstractMenuRenderer
{
    /**
     * @inheritDoc
     */
    protected function getMenuOptionDefinition(): array
    {
        return [
            'levels' => '__UNSET',
            'offsetStart' => [
                'type' => 'int',
                'default' => 0,
            ],
            'offsetEnd' => [
                'type' => 'int',
                'default' => 0,
            ],
        ];
    }
    
    /**
     * @inheritDoc
     */
    protected function getMenuTsConfig(array $defaultDefinition): array
    {
        $defaultDefinition['special'] = 'rootline';
        $defaultDefinition['special.'] = [
            'value.' => [
                'range' => $this->options['offsetStart'] . '|' .
                           (empty($this->options['offsetEnd']) ? '999' : (-abs($this->options['offsetEnd']) - 1)),
            ],
        ];
        
        return $defaultDefinition;
    }
    
}
