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
 * Last modified: 2020.11.26 at 13:50
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Event;


class TransformerCircularDependencyFilterEvent
{

    /**
     * The value which lead to the circular dependency
     *
     * @var mixed
     */
    protected $circularValue;

    /**
     * The circular dependency path
     *
     * @var array
     */
    protected $path;

    /**
     * Contains the resolved value if it was set by this event
     *
     * @var null|array
     */
    protected $resolvedValue;

    public function __construct(array $path, $circularValue)
    {
        $this->path          = $path;
        $this->circularValue = $circularValue;
    }

    /**
     * Returns the value which lead to the circular dependency
     *
     * @return mixed
     */
    public function getCircularValue()
    {
        return $this->circularValue;
    }

    /**
     * Returns the circular dependency path
     *
     * @return array
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * Returns the resolved value if it was set by this event or null if there was nothing set yet
     *
     * @return array|null
     */
    public function getResolvedValue(): ?array
    {
        return $this->resolvedValue;
    }

    /**
     * Allows you to provide the resolved value for a circular dependency.
     * If this is set to NULL the auto-transformer will resolve the value with a dynamic reference
     *
     * @param   array|null  $resolvedValue
     *
     * @return TransformerCircularDependencyFilterEvent
     */
    public function setResolvedValue(?array $resolvedValue): TransformerCircularDependencyFilterEvent
    {
        $this->resolvedValue = $resolvedValue;

        return $this;
    }


}
