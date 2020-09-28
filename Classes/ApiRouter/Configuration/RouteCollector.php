<?php
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
 * Last modified: 2019.08.26 at 14:15
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Configuration;


use LaborDigital\Typo3BetterApi\Cache\CacheStrategyInterface;
use Neunerlei\Options\Options;

class RouteCollector
{

    /**
     * The list of routes that were collected
     *
     * @var RouteConfig[]
     */
    protected $routes = [];

    /**
     * The name of the controller class we collecting the routes for
     *
     * @var string
     */
    protected $controllerClass;

    /**
     * The group base uri for the configured routes
     *
     * @var string
     */
    protected $groupBaseUri;

    /**
     * Can be used to override the collector's controller class
     *
     * @param   string  $controllerClass
     *
     * @return $this
     */
    public function setControllerClass(string $controllerClass)
    {
        $this->controllerClass = $controllerClass;

        return $this;
    }

    /**
     * Returns the current group base uri, or an empty string if there is none.
     *
     * @return string
     */
    public function getGroupBaseUri(): string
    {
        return $this->groupBaseUri;
    }

    /**
     * Sets the group base uri from the outside
     *
     * @param   string  $groupBaseUri
     *
     * @return RouteCollector
     */
    public function setGroupBaseUri(string $groupBaseUri): RouteCollector
    {
        $this->groupBaseUri = $groupBaseUri;

        return $this;
    }

    /**
     * Can be used to amp a method for any HTTP method on a given path
     *
     * @param   string  $method        GET, POST, PUT ...
     * @param   string  $path          The route to listen to
     * @param   string  $actionMethod  The controller action method to bind
     * @param   array   $options       Additional options:
     *                                 - name: string Can be used to create a named route. This is a unique identifier for
     *                                 each route! If the route name already exists it will be overwritten with this
     *                                 configuration!. If it is left empty an automatic identifier is set.
     *                                 - strategy: string Can be used to set a strategy for this handler only
     *                                 - middlewares: array Can be used to add additional middle wares that only apply to
     *                                 this route
     *
     *
     *                                 DEPRECATED:
     *                                 - useCache: bool By default all GET requests are cached to speed up delivery time.
     *                                 If you don't want to cache a request, set this to false.
     *
     * @return \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteConfig
     */
    public function map(string $method, string $path, string $actionMethod, array $options = []): RouteConfig
    {
        // Make the options
        $options = Options::make($options, [
            'name'        => [
                'type'    => ['string', 'null'],
                'default' => null,
            ],
            'cache'       => [
                'type'     => 'array',
                'default'  => [],
                'children' => [
                    'ttl'     => [
                        'type'    => ['int', 'null'],
                        'default' => null,
                    ],
                    'enabled' => [
                        'type'    => 'bool',
                        'default' => true,
                    ],
                ],
            ],
            'useCache'    => [
                'type'    => 'boolean',
                'default' => true,
            ],
            'strategy'    => [
                'type'    => ['string', 'null'],
                'default' => null,
            ],
            'middlewares' => [
                'default'   => [],
                'type'      => ['array'],
                'preFilter' => function ($v) {
                    if (! is_array($v)) {
                        return [$v];
                    }

                    return $v;
                },
            ],
        ]);

        // Make a new route
        $route = RouteConfig::makeNew($method, $path, $this->controllerClass, $actionMethod);
        $route->setMiddlewares($options['middlewares']);
        $route->setStrategy($options['strategy']);
        $route->setCacheOptionsArray($options['cache']);
        $route->setCacheEnabled($options['useCache'] && $options['cache']['enabled']);
        if (! empty($options['name'])) {
            $route->setName($options['name']);
        } elseif (! empty($this->groupBaseUri)) {
            $route->setName($this->groupBaseUri . '-' . $route->getName());
        }

        // Store the route
        $this->routes[$route->getName()] = $route;

        return $route;
    }

    /**
     * Returns true if a route with the given name already exists
     *
     * @param   string  $routeName
     *
     * @return bool
     */
    public function hasRoute(string $routeName): bool
    {
        return isset($this->routes[$routeName]);
    }

    /**
     * Removes a previously mapped route from the configuration
     *
     * @param   string  $routeName  The name of the route to remove
     *
     * @return $this
     */
    public function unmap(string $routeName)
    {
        unset($this->routes[$routeName]);

        return $this;
    }

    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param   string  $path
     * @param   string  $actionMethod
     * @param   array   $options
     *
     * @return RouteConfig
     * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector::map() for parameter descriptions
     */
    public function get($path, string $actionMethod, array $options = []): RouteConfig
    {
        return $this->map('GET', $path, $actionMethod, $options);
    }

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param   string  $path
     * @param   string  $actionMethod
     * @param   array   $options
     *
     * @return RouteConfig
     * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector::map() for parameter descriptions
     */
    public function post($path, string $actionMethod, array $options = []): RouteConfig
    {
        return $this->map('POST', $path, $actionMethod, $options);
    }

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param   string  $path
     * @param   string  $actionMethod
     * @param   array   $options
     *
     * @return RouteConfig
     * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector::map() for parameter descriptions
     */
    public function put($path, string $actionMethod, array $options = []): RouteConfig
    {
        return $this->map('PUT', $path, $actionMethod, $options);
    }

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param   string  $path
     * @param   string  $actionMethod
     * @param   array   $options
     *
     * @return RouteConfig
     * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector::map() for parameter descriptions
     */
    public function patch($path, string $actionMethod, array $options = []): RouteConfig
    {
        return $this->map('PATCH', $path, $actionMethod, $options);
    }

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param   string  $path
     * @param   string  $actionMethod
     * @param   array   $options
     *
     * @return RouteConfig
     * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector::map() for parameter descriptions
     */
    public function delete($path, string $actionMethod, array $options = []): RouteConfig
    {
        return $this->map('DELETE', $path, $actionMethod, $options);
    }

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param   string  $path
     * @param   string  $actionMethod
     * @param   array   $options
     *
     * @return RouteConfig
     * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector::map() for parameter descriptions
     */
    public function head($path, string $actionMethod, array $options = []): RouteConfig
    {
        return $this->map('HEAD', $path, $actionMethod, $options);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param   string  $path
     * @param   string  $actionMethod
     * @param   array   $options
     *
     * @return RouteConfig
     * @see \LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector::map() for parameter descriptions
     */
    public function options($path, string $actionMethod, array $options = []): RouteConfig
    {
        return $this->map('OPTIONS', $path, $actionMethod, $options);
    }

    /**
     * Returns all collected routes as an array with their configuration
     *
     * @return RouteConfig[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
