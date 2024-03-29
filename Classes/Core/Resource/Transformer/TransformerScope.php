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
 * Last modified: 2021.05.19 at 22:12
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer;


class TransformerScope
{
    /**
     * While true fractal will auto-include all related resources automatically
     *
     * @var bool
     */
    public static $allIncludes = false;
    
    /**
     * While false the property access check will be disabled
     *
     * @var bool
     */
    public static $accessCheck = true;
    
    /**
     * Defines the path through all objects that are in the current chain of transformation
     *
     * @var array
     */
    public static $path = [];
    
    /**
     * A cache of all transformed object data entries to resolve circular dependencies
     *
     * @var array
     */
    public static $transformed = [];
}