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
 * Last modified: 2020.03.20 at 18:59
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use Psr\Http\Message\ServerRequestInterface;

/**
 * Class FrontendSimulationMiddlewareFilterEvent
 *
 * Dispatched in the frontend simulation middleware.
 * It allows you to modify the collected information before the frontend simulation starts
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class FrontendSimulationMiddlewareFilterEvent
{

    /**
     * The language either as numeric value if "L" parameter was given or as object
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage|int|null
     */
    protected $language;

    /**
     * The page id of the request
     *
     * @var int
     */
    protected $pid;

    /**
     * The prepared request object
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * FrontendSimulationMiddlewareFilterEvent constructor.
     *
     * @param                                             $language
     * @param   int                                       $pid
     * @param   \Psr\Http\Message\ServerRequestInterface  $request  *
     */
    public function __construct($language, int $pid, ServerRequestInterface $request)
    {
        $this->language = $language;
        $this->pid      = $pid;
        $this->request  = $request;
    }

    /**
     * Returns the language either as numeric value if "L" parameter was given or as object
     *
     * @return int|\TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Can be used to update the language used for the simulation
     *
     * @param   int|\TYPO3\CMS\Core\Site\Entity\SiteLanguage|null  $language
     *
     * @return FrontendSimulationMiddlewareFilterEvent
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Returns the page id of the request
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * Updates the page id of the request
     *
     * @param   int  $pid
     *
     * @return FrontendSimulationMiddlewareFilterEvent
     */
    public function setPid(int $pid): FrontendSimulationMiddlewareFilterEvent
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Returns the prepared request object
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Updates the request object
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return FrontendSimulationMiddlewareFilterEvent
     */
    public function setRequest(ServerRequestInterface $request): FrontendSimulationMiddlewareFilterEvent
    {
        $this->request = $request;

        return $this;
    }

}
