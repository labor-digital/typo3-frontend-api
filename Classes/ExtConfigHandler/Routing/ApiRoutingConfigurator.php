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
 * Last modified: 2021.05.16 at 16:47
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Routing;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;

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
    
}