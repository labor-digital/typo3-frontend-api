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
 * Last modified: 2019.08.26 at 14:31
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Configuration;


class ResourceConfig
{
    
    /**
     * The resource type we are currently configuring
     *
     * @var string
     */
    public $resourceType;
    
    /**
     * Holds the list of properties that should be added to the output
     * if the element was transformed into an array.
     *
     * Note: This affects the resulting array no matter which translator class you use.
     *
     * @var array
     */
    public $allowedProperties = [];
    
    /**
     * Holds the list of properties that should never show up in the output
     * if the element was transformed into an array.
     *
     * The denied properties have higher priority than the allowed properties.
     * Meaning a property that shows up in both arrays, will be removed from the output anyway.
     *
     * Note: This affects the resulting array no matter which translator class you use.
     *
     * @var array
     */
    public $deniedProperties = [];
    
    // @todo implement this better -> Would work well in the AbstractTransformer
    public $includeInternalProperties = false;
    
    /**
     * The number of items that should be displayed on a single page when the "collection" route is called
     *
     * @var int
     */
    public $pageSize = 15;
    
    /**
     * The class name that should act as a controller for this resource
     *
     * @var string
     */
    public $controllerClass;
    
    /**
     * A list of classes / entities that should be mapped to this resource type
     *
     * @var array
     */
    public $classes = [];
    
    /**
     * The class of the resource transformer that should convert the resource into an array
     * If null the default transformer is used
     *
     * @var string|null
     */
    public $transformerClass;
    
    /**
     * A list of optional post processors.
     * Those will run after the main transformer class was executed and can add additional fields to a value
     *
     * @var array
     */
    public $transformerPostProcessors = [];
}
