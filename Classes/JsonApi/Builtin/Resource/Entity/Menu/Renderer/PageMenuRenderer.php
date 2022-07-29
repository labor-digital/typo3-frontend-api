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
 * Last modified: 2020.09.29 at 15:54
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer;


class PageMenuRenderer extends AbstractMenuRenderer
{
    /**
     * @inheritDoc
     */
    protected function getMenuOptionDefinition(): array
    {
        return [
            'showSpacers' => [
                'type'    => 'bool',
                'default' => false,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getMenuTsConfig(array $defaultDefinition): array
    {
        $defaultDefinition['special']  = 'list';
        $defaultDefinition['special.'] = [
            'value.' => [
                'field' => 'pages',
            ],
        ];

        return $defaultDefinition;
    }

}