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
 * Last modified: 2020.03.20 at 20:10
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3BetterApi\Event\Events\FrontendAssetPostProcessorEvent;

/**
 * Class HybridApiDataPostProcessorEvent
 *
 * Dispatched when the hybrid api data is been appended to the asset list
 * Allows you to modify the global hybrid data before it is wrapped into a script tag
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class HybridApiDataPostProcessorEvent
{
    /**
     * The configured window variable name where the global data and the translation labels will be stored initially
     *
     * @var string
     */
    protected $windowVar;

    /**
     * The api data that will be added to the head of the page.
     * Already converted into an array using the transformer.
     *
     * @var array
     */
    protected $data;

    /**
     * The parent event to allow additional asset manipulation
     *
     * @var \LaborDigital\Typo3BetterApi\Event\Events\FrontendAssetPostProcessorEvent
     */
    protected $parentEvent;

    /**
     * HybridApiDataPostProcessorEvent constructor.
     *
     * @param   string                                                                     $windowVar
     * @param   array                                                                      $data
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\FrontendAssetPostProcessorEvent  $parentEvent
     */
    public function __construct(string $windowVar, array $data, FrontendAssetPostProcessorEvent $parentEvent)
    {
        $this->windowVar   = $windowVar;
        $this->data        = $data;
        $this->parentEvent = $parentEvent;
    }

    /**
     * Return the parent event to allow additional asset manipulation
     *
     * @return \LaborDigital\Typo3BetterApi\Event\Events\FrontendAssetPostProcessorEvent
     */
    public function getParentEvent(): FrontendAssetPostProcessorEvent
    {
        return $this->parentEvent;
    }

    /**
     * Returns the configured window variable name where the global data and the translation labels will be stored
     * initially
     *
     * @return string
     */
    public function getWindowVar(): string
    {
        return $this->windowVar;
    }

    /**
     * Used to update the configured window variable name where the global data and the translation labels will be
     * stored initially
     *
     * @param   string  $windowVar
     *
     * @return HybridApiDataPostProcessorEvent
     */
    public function setWindowVar(string $windowVar): HybridApiDataPostProcessorEvent
    {
        $this->windowVar = $windowVar;

        return $this;
    }

    /**
     * Returns the api data that will be added to the head of the page
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Used to update the modified api data that will be added to the head of the page
     *
     * @param   array  $data
     *
     * @return HybridApiDataPostProcessorEvent
     */
    public function setData(array $data): HybridApiDataPostProcessorEvent
    {
        $this->data = $data;

        return $this;
    }

}
