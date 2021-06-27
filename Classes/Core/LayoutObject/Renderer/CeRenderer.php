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
 * Last modified: 2021.06.21 at 20:22
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\LayoutObject\Renderer;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Api\Resource\Factory\ContentElement\ContentElementResourceFactory;
use LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory;

class CeRenderer implements PublicServiceInterface
{
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\ContentElement\ContentElementResourceFactory
     */
    protected $ceFactory;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Backend\ResourceFactory
     */
    protected $resourceFactory;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(
        ContentElementResourceFactory $ceFactory,
        ResourceFactory $resourceFactory,
        EnvironmentSimulator $simulator,
        TypoContext $typoContext
    )
    {
        $this->ceFactory = $ceFactory;
        $this->resourceFactory = $resourceFactory;
        $this->simulator = $simulator;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Renders an element of the tt_content table as a resource array
     *
     * @param   int    $uid                The uid of the element in the tt_content table
     * @param   array  $options            Additional options to simulate a specific environment:
     *                                     {@link EnvironmentSimulator::runWithEnvironment()}
     *
     * @return array
     */
    public function renderWithId(int $uid, array $options = []): array
    {
        return $this->simulator->runWithEnvironment($options, function () use ($uid) {
            return $this->resourceFactory->makeResourceItem(
                $this->ceFactory->makeFromId($uid, $this->typoContext->language()->getCurrentFrontendLanguage())
            )->asArray(['jsonApi', 'include' => true]);
        });
    }
    
    /**
     * Renders an element based on the typo script configuration on a given path
     *
     * @param   string  $typoScriptObjectPath  The typo script object path to find the definition at
     * @param   array   $options               Additional options to simulate a specific environment:
     *                                         {@link EnvironmentSimulator::runWithEnvironment()}
     *
     * @return array
     */
    public function renderWithPath(string $typoScriptObjectPath, array $options = []): array
    {
        return $this->simulator->runWithEnvironment($options, function () use ($typoScriptObjectPath) {
            return $this->resourceFactory->makeResourceItem(
                $this->ceFactory->makeFromTypoScriptPath($typoScriptObjectPath, $this->typoContext->language()->getCurrentFrontendLanguage())
            )->asArray(['jsonApi', 'include' => true]);
        });
    }
}