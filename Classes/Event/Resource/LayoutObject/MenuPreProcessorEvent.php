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
 * Last modified: 2021.06.25 at 18:36
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Event\Resource\LayoutObject;

/**
 * Class MenuPreProcessorEvent
 *
 * Emitted when a menu object is rendered in based on the site configuration.
 * Receives the prepared typoScript object and might intersect the default rendering
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class MenuPreProcessorEvent
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
     * @deprecated will be removed in v10
     */
    protected $render = true;
    
    /**
     * The unique key of the menu that is processed
     *
     * @var string
     */
    protected $key;
    
    /**
     * The options configured for this menu
     *
     * @var array
     */
    protected $options;
    
    /**
     * MenuPreProcessorEvent constructor.
     *
     * @param   array   $definition
     * @param   string  $type
     * @param   string  $key
     * @param   array   $options
     */
    public function __construct(array $definition, string $type, string $key, array $options)
    {
        $this->definition = $definition;
        $this->type = $type;
        $this->key = $key;
        $this->options = $options;
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
     * @return MenuPreProcessorEvent
     */
    public function setDefinition(array $definition): self
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
     * Returns the unique key of the menu that is processed
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
    
    /**
     * Returns the options configured for this menu
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
    
    /**
     * Returns true as long as the menu should be rendered by the typo script controller
     *
     * @return bool
     * @deprecated will be removed in v10
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
     * @return MenuPreProcessorEvent
     * @deprecated will be removed in v10
     */
    public function setRender(bool $render): MenuPreProcessorEvent
    {
        $this->render = $render;
        
        return $this;
    }
}
