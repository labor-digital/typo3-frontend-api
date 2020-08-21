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
 * Last modified: 2020.03.20 at 20:45
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

/**
 * Class SiteMenuPreProcessorEvent
 *
 * Emitted when a menu object is rendered in based on the site configuration.
 * Receives the prepared typoscript object and might intersect the default rendering
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class SiteMenuPreProcessorEvent
{
    /**
     * The prepared typo script definition of the menu
     *
     * @var array
     */
    protected $definition;

    /**
     * The menu type to filter / generate
     *
     * @var string
     */
    protected $type;

    /**
     * True as long as the menu should be rendered by the typo script controller
     *
     * @var bool
     */
    protected $render = true;

    /**
     * SiteMenuPreProcessorEvent constructor.
     *
     * @param   array   $definition
     * @param   string  $type
     */
    public function __construct(array $definition, string $type)
    {
        $this->definition = $definition;
        $this->type       = $type;
    }

    /**
     * Returns the prepared typo script definition of the menu
     *
     * @return array
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * Updates the prepared typo script definition of the menu
     *
     * @param   array  $definition
     *
     * @return SiteMenuPreProcessorEvent
     */
    public function setDefinition(array $definition): SiteMenuPreProcessorEvent
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * Returns the menu type to filter / generate
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns true as long as the menu should be rendered by the typo script controller
     *
     * @return bool
     */
    public function isRender(): bool
    {
        return $this->render;
    }

    /**
     * Defines if the menu should be rendered by the typo script controller
     *
     * @param   bool  $render
     *
     * @return SiteMenuPreProcessorEvent
     */
    public function setRender(bool $render): SiteMenuPreProcessorEvent
    {
        $this->render = $render;

        return $this;
    }
}
