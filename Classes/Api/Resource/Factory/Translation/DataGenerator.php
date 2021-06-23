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
 * Last modified: 2021.06.23 at 13:13
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\Translation;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3ba\Tool\Translation\Translator;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use Neunerlei\Arrays\Arrays;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class DataGenerator implements PublicServiceInterface
{
    
    /**
     * @var \LaborDigital\T3ba\Tool\Translation\Translator
     */
    protected $translator;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        Translator $translator,
        TypoContext $typoContext,
        EnvironmentSimulator $simulator,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->translator = $translator;
        $this->typoContext = $typoContext;
        $this->simulator = $simulator;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Generates the constructor arguments for a translation entity
     *
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage   $language
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteInterface  $site
     *
     * @return array
     */
    public function generate(SiteLanguage $language, SiteInterface $site): array
    {
        return $this->simulator->runWithEnvironment([
            'language' => $language,
            'site' => $site->getIdentifier(),
        ], function () use ($language, $site): array {
            return [
                $language->getTwoLetterIsoCode(),
                [
                    'labels' => $this->generateLabels(),
                    'meta' => [
                        'language' => $language->getTwoLetterIsoCode(),
                        'site' => $site->getIdentifier(),
                    ],
                ],
            ];
        });
    }
    
    /**
     * Generates the list of labels based on the configured translation label files
     *
     * @return array
     */
    protected function generateLabels(): array
    {
        $config = $this->typoContext->config()->getSiteBasedConfigValue('t3fa.translation', []);
        if (empty($config['labelFiles'])) {
            return [];
        }
        
        $labels = [];
        $pluralLabels = [];
        
        $convertSprintf = (bool)($config['convertSprintfPlaceholders'] ?? false);
        $combinePlurals = (bool)($config['pluralsAsArray'] ?? false);
        foreach ($config['labelFiles'] as $file) {
            foreach ($this->translator->getAllKeysInFile($file) as $label => $labelWithFileName) {
                $translated = $this->translator->translate($labelWithFileName);
                
                // Convert %s sprintf formats to {0}... formats for js frameworks to cope with
                if ($convertSprintf) {
                    $c = 0;
                    $translated = preg_replace_callback('~%s~i', static function () use (&$c) {
                        return '{' . $c++ . '}';
                    }, $translated);
                }
                
                // Combine plural values in an array under the combined label key
                if ($combinePlurals && preg_match('~(.*?)\[\d+]$~', $label, $m)) {
                    $pluralLabels[$m[1]][] = $translated;
                    continue;
                }
                
                $labels[$label] = $translated;
            }
        }
        
        $labels = array_merge($labels, $pluralLabels);
        $labels = Arrays::unflatten($labels);
        
        // @todo an event would be nice here
        
        return $labels;
    }
}