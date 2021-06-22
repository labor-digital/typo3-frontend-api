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
 * Last modified: 2021.06.21 at 15:11
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\LayoutObject;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class DataGenerator implements PublicServiceInterface
{
    use ContainerAwareTrait;
    use T3faCacheAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(EnvironmentSimulator $simulator, TypoContext $typoContext)
    {
        $this->simulator = $simulator;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Generates the constructor arguments for a layout object entity
     *
     * @param   string                                    $identifier
     * @param   string                                    $generatorClass
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return array
     */
    public function generate(string $identifier, string $generatorClass, SiteLanguage $language): array
    {
        return $this->simulator->runWithEnvironment(
            [
                'language' => $language,
                'includeHiddenPages' => $this->typoContext->preview()->isPreview(),
            ],
            function () use ($identifier, $generatorClass, $language) {
                $context = $this->makeInstance(LayoutObjectContext::class, [$identifier, $language]);
                
                /** @var \LaborDigital\T3fa\Core\LayoutObject\LayoutObjectInterface $generator */
                $generator = $this->getServiceOrInstance($generatorClass);
                
                $result = $generator->generate($context);
                
                $result['meta']['language'] = $language->getTwoLetterIsoCode();
                
                $this->runInCacheScope(function (Scope $scope) use ($context) {
                    $scope->setCacheOptions($context->getCacheOptions(), true);
                });
                
                return [$identifier, $result];
            }
        );
    }
}