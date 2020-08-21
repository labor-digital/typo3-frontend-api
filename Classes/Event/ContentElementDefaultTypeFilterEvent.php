<?php
/**
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
 * Last modified: 2020.03.20 at 19:25
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

/**
 * Class ContentElementDefaultTypeFilterEvent
 *
 * Allows you to filter the default content element tca array before the configuration
 * is compiled and put back into the tca array
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ContentElementDefaultTypeFilterEvent
{

    /**
     * The tca array that is by used as a default for a new content element
     *
     * @var array
     */
    protected $type;

    /**
     * ContentElementDefaultTypeFilterEvent constructor.
     *
     * @param   array  $type
     */
    public function __construct(array $type)
    {
        $this->type = $type;
    }

    /**
     * Returns the tca array that is by used as a default for a new content element
     *
     * @return array
     */
    public function getType(): array
    {
        return $this->type;
    }

    /**
     * Updates the tca array that is by used as a default for a new content element
     *
     * @param   array  $type
     *
     * @return ContentElementDefaultTypeFilterEvent
     */
    public function setType(array $type): ContentElementDefaultTypeFilterEvent
    {
        $this->type = $type;

        return $this;
    }
}
