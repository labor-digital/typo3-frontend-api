<?php
declare(strict_types=1);
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
 * Last modified: 2019.08.20 at 17:39
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration;

class ContentElementConfig
{
    /**
     * The typoScript configuration required for the registered content objects
     *
     * @var string
     */
    public $typoScript;

    /**
     * The changed tt content tca that was updated by the config generator
     *
     * @var array
     */
    public $ttContentTca = [];

    /**
     * Contains the list of virtual columns for each content element
     *
     * @var array
     */
    public $virtualColumns = [];

    /**
     * Contains the sql definitions for additional tables this element requires
     *
     * @var string
     */
    public $sql = "";

    /**
     * The list of backend action handlers that are registered for our content elements
     *
     * @var array
     */
    public $dataHandlerActionHandlers = [];

    /**
     * The ts config string required for the registered elements
     *
     * @var string
     */
    public $tsConfig = "";

    /**
     * The list of icon definitions to register
     *
     * @var array
     */
    public $iconDefinitionArgs = [];

    /**
     * The list of backend preview renderer registration arguments
     *
     * @var array
     */
    public $backendPreviewRenderers = [];

    /**
     * The list of backend list label renderer registration arguments
     *
     * @var array
     */
    public $backendListLabelRenderers = [];
}
