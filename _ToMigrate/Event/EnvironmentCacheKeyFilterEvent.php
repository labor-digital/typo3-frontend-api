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
 * Last modified: 2020.11.18 at 14:06
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Event;

/**
 * Class EnvironmentCacheKeyFilterEvent
 *
 * Receives the prepared list of environment cache key arguments that you can enhance for your project's requirements.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class EnvironmentCacheKeyFilterEvent
{
    /**
     * The prepared cache key arguments
     *
     * @var array
     */
    protected $args;

    /**
     * EnvironmentCacheKeyFilterEvent constructor.
     *
     * @param   array  $args
     */
    public function __construct(array $args)
    {
        $this->args = $args;
    }

    /**
     * Returns the prepared cache key arguments
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Updates the prepared cache key arguments
     *
     * @param   array  $args
     *
     * @return EnvironmentCacheKeyFilterEvent
     */
    public function setArgs(array $args): self
    {
        $this->args = $args;

        return $this;
    }
}
