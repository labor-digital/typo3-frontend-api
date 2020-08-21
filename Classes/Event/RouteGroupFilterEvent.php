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
 * Last modified: 2020.03.20 at 19:06
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;

/**
 * Class RouteGroupFilterEvent
 *
 * Emitted after the route groups have been collected and before they are
 * stored in the cache value. Can be used for dynamic route adjustments at the last minute.
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class RouteGroupFilterEvent
{

    /**
     * The list of route groups that have been collected.
     * The list is already sorted by the dependencies
     *
     * @var array
     */
    protected $groups;

    /**
     * The ext config context
     *
     * @var \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
     */
    protected $context;

    /**
     * RouteGroupFilterEvent constructor.
     *
     * @param   array                                                    $groups
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext  $context
     */
    public function __construct(array $groups, ExtConfigContext $context)
    {
        $this->groups  = $groups;
        $this->context = $context;
    }

    /**
     * Return the ext config context
     *
     * @return \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
     */
    public function getContext(): ExtConfigContext
    {
        return $this->context;
    }

    /**
     * Returns the list of route groups that have been collected
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Used to update the list of route groups that have been collected
     *
     * @param   array  $groups
     *
     * @return RouteGroupFilterEvent
     */
    public function setGroups(array $groups): RouteGroupFilterEvent
    {
        $this->groups = $groups;

        return $this;
    }

}
