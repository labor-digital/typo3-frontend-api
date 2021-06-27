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
 * Last modified: 2021.06.16 at 11:06
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'T3FA',
    'description' => 'Provides a frontend API handler with support for Single Page Apps',
    'author' => 'Martin Neundorfer',
    'author_email' => 'm.neundorfer@labor.digital',
    'category' => 'fe',
    'author_company' => 'Labor.digital',
    'shy' => '',
    'conflicts' => '',
    'priority' => '',
    'module' => '',
    'state' => 'alpha',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'version' => '9.37.2',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-10.99.99',
            't3ba' => '10.0.0-10.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'suggests' => [
    ],
];
