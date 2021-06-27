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
 * Last modified: 2021.06.22 at 21:53
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Routing;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use Neunerlei\Options\Options;

class ApiRoutingConfigurator extends AbstractExtConfigConfigurator
{
    /**
     * The path on which the frontend api will be listening
     *
     * @var string
     */
    protected $apiPath;
    
    /**
     * True if the speaking error handler should be used
     * False if the speaking error handler should NEVER be used
     * Null if the environment automatically decides when to use which error handler.
     *
     * @var bool|null
     */
    protected $useSpeakingErrorHandler;
    
    /**
     * True if the /api/up route should be available on ALL sites
     *
     * @var bool
     */
    protected $upRoute = false;
    
    /**
     * If set, contains the scheduler route options provided
     *
     * @var array|null
     */
    protected $schedulerRoute;
    
    /**
     * Returns true if the API should use the speaking error handler
     * If this returns null, the environment will decide
     *
     * @return bool|null
     */
    public function useSpeakingErrorHandler(): ?bool
    {
        return $this->useSpeakingErrorHandler;
    }
    
    /**
     * Sets if the speaking error handler is used
     *
     * True if the speaking error handler should be used
     * False if the speaking error handler should NEVER be used
     * Null if the environment automatically decides when to use which error handler.
     *
     * @param   bool|null  $state
     *
     * @return $this
     */
    public function setUseSpeakingErrorHandler(?bool $state): self
    {
        $this->useSpeakingErrorHandler = $state;
        
        return $this;
    }
    
    /**
     * Returns the uri path on which the frontend api will be listening
     * It is the main entry point to all your API endpoints
     *
     * @return string
     */
    public function getApiPath(): string
    {
        return $this->apiPath;
    }
    
    /**
     * Sets the uri path on which the frontend api will be listening
     * It is the main entry point to all your API endpoints
     *
     * @param   string  $apiPath
     *
     * @return $this
     */
    public function setApiPath(string $apiPath): self
    {
        $apiPath = trim($apiPath, '/');
        
        if (empty($apiPath)) {
            throw new \InvalidArgumentException('An empty api path is not allowed!');
        }
        
        $this->apiPath = '/' . $apiPath;
        
        return $this;
    }
    
    /**
     * Enables a route in the api router that can be accessed on the /api/up url.
     * It just returns "OK" and a state of 200 if the system is running as desired.
     * The route will be enabled on ALL sites.
     *
     * @return $this
     */
    public function enableUpRoute(): self
    {
        $this->upRoute = true;
        
        return $this;
    }
    
    /**
     * Disables the /api/up endpoint if it was previously enabled.
     *
     * @return $this
     */
    public function disableUpRoute(): self
    {
        $this->upRoute = false;
        
        return $this;
    }
    
    /**
     * Registers a route in the frontend api to execute the TYPO3 scheduler via HTTP request.
     * The endpoint is accessible on /api/scheduler/run to run the whole scheduler task list
     * If you provide the id of a given task like /api/scheduler/run/1 for task ID 1 you can also execute a single task.
     * While you are running in a Dev environment and execute a single task it will always be forced to run, ignoring the cronjob configuration;
     * can be used to debug your scheduler tasks locally.
     *
     * @param   string|array  $token    Defines either a single or multiple tokens that act as "password" to
     *                                  access the scheduler endpoint. The token can either be received using the
     *                                  "x-t3fa-token" header or via query parameter "token",
     *                                  if that option was enabled by setting "allowTokenInQuery" to true
     * @param   array         $options  The options to configure the scheduler execution
     *                                  - maxExecutionTime int (60*10): The number in seconds the php script
     *                                  can run before it is forcefully killed by the server.
     *                                  - allowTokenInQuery bool (FALSE): If set to true the token may be passed by query
     *                                  parameter instead of a HTTP header. This is TRUE by default if you are running
     *                                  in a dev environment.
     *
     * @return $this
     */
    public function enableSchedulerRoute($token, array $options = []): self
    {
        $this->schedulerRoute = Options::make(
            array_merge($options, ['token' => $token]),
            [
                'maxExecutionTime' => [
                    'type' => 'int',
                    'default' => 60 * 10,
                ],
                'token' => [
                    'type' => ['string', 'array'],
                    'filter' => function ($v) {
                        if (is_string($v)) {
                            return [$v];
                        }
                        
                        return array_values($v);
                    },
                ],
                'allowTokenInQuery' => [
                    'type' => 'bool',
                    'default' => false,
                ],
            ]
        );
        
        return $this;
    }
    
    /**
     * Disables the scheduler route and resets the configuration.
     *
     * @return $this
     */
    public function disableSchedulerRoute(): self
    {
        $this->schedulerRoute = null;
        
        return $this;
    }
}