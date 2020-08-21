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
 * Last modified: 2020.03.20 at 19:34
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use Psr\Http\Message\ServerRequestInterface;

class ContentElementPreProcessorEvent
{
    /**
     * The c-type that is used as unique key for the content element
     *
     * @var string
     */
    protected $cType;

    /**
     * The raw database row of the tt_content record to render
     *
     * @var array
     */
    protected $row;

    /**
     * The controller class that should be used to generate the result
     *
     * @var string
     */
    protected $controllerClass;

    /**
     * The server request object
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * True if the frontend action should be called, false if not
     *
     * @var bool
     */
    protected $isFrontend;

    /**
     * The typoScript configuration array for this element
     *
     * @var array
     */
    protected $config;

    /**
     * Additional data that will be passed to the controller context object
     *
     * @var array
     */
    protected $environment;

    /**
     * ContentElementPreProcessorEvent constructor.
     *
     * @param   string                                    $cType
     * @param   array                                     $row
     * @param   string                                    $controllerClass
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   bool                                      $isFrontend
     * @param   array                                     $config
     * @param   array                                     $environment
     */
    public function __construct(
        string $cType,
        array $row,
        string $controllerClass,
        ServerRequestInterface $request,
        bool $isFrontend,
        array $config,
        array $environment
    ) {
        $this->cType           = $cType;
        $this->row             = $row;
        $this->controllerClass = $controllerClass;
        $this->request         = $request;
        $this->isFrontend      = $isFrontend;
        $this->config          = $config;
        $this->environment     = $environment;
    }

    /**
     * Returns true if the frontend action should be called, false if not
     *
     * @return bool
     */
    public function isFrontend(): bool
    {
        return $this->isFrontend;
    }

    /**
     * Returns the c-type that is used as unique key for the content element
     *
     * @return string
     */
    public function getCType(): string
    {
        return $this->cType;
    }

    /**
     * Updates the c-type that is used as unique key for the content element
     *
     * @param   string  $cType
     *
     * @return ContentElementPreProcessorEvent
     */
    public function setCType(string $cType): ContentElementPreProcessorEvent
    {
        $this->cType = $cType;

        return $this;
    }

    /**
     * Returns the raw database row of the tt_content record to render
     *
     * @return array
     */
    public function getRow(): array
    {
        return $this->row;
    }

    /**
     * Sets the raw database row of the tt_content record to render
     *
     * @param   array  $row
     *
     * @return ContentElementPreProcessorEvent
     */
    public function setRow(array $row): ContentElementPreProcessorEvent
    {
        $this->row = $row;

        return $this;
    }

    /**
     * Returns the controller class that should be used to generate the result
     *
     * @return string
     */
    public function getControllerClass(): string
    {
        return $this->controllerClass;
    }

    /**
     * Updates the controller class that should be used to generate the result
     *
     * @param   string  $controllerClass
     *
     * @return ContentElementPreProcessorEvent
     */
    public function setControllerClass(string $controllerClass): ContentElementPreProcessorEvent
    {
        $this->controllerClass = $controllerClass;

        return $this;
    }

    /**
     * Returns the server request object
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Can be used to update the server request object
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return ContentElementPreProcessorEvent
     */
    public function setRequest(ServerRequestInterface $request): ContentElementPreProcessorEvent
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Returns the typoScript configuration array for this element
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Updates the typoScript configuration array for this element
     *
     * @param   array  $config
     *
     * @return ContentElementPreProcessorEvent
     */
    public function setConfig(array $config): ContentElementPreProcessorEvent
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Returns additional data that will be passed to the controller context object
     *
     * @return array
     */
    public function getEnvironment(): array
    {
        return $this->environment;
    }

    /**
     * Updates the additional data that will be passed to the controller context object
     *
     * @param   array  $environment
     *
     * @return ContentElementPreProcessorEvent
     */
    public function setEnvironment(array $environment): ContentElementPreProcessorEvent
    {
        $this->environment = $environment;

        return $this;
    }


}
