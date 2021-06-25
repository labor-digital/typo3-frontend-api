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
 * Last modified: 2021.06.25 at 19:08
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Event\Routing;


use League\Route\Router;

/**
 * Class RouterInstanceFilterEvent
 *
 * Emitted every time the RouterFactory generates a new instance of the router.
 * Allows you to modify the router instance at the last minute. Allows you to hook in middlewares
 * or routes that have issues in the caching mechanic.
 *
 * @package LaborDigital\T3fa\Event\Routing
 */
class RouterInstanceFilterEvent
{
    /**
     * The router instance to filter
     *
     * @var \League\Route\Router
     */
    protected $router;
    
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    
    /**
     * Returns the router instance to filter
     *
     * @return \League\Route\Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Updates the router instance to filter
     *
     * @param   \League\Route\Router  $router
     *
     * @return RouterInstanceFilterEvent
     */
    public function setRouter(Router $router): RouterInstanceFilterEvent
    {
        $this->router = $router;
        
        return $this;
    }
    
}