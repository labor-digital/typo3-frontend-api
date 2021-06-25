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
 * Last modified: 2021.06.25 at 18:49
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\LayoutObject\Renderer\Menu;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\OddsAndEnds\NamingUtil;
use LaborDigital\T3ba\Tool\Tsfe\TsfeService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformer;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Options\Options;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Frontend\DataProcessing\FilesProcessor;

abstract class AbstractMenuRenderer implements PublicServiceInterface
{
    use ContainerAwareTrait;
    use T3faCacheAwareTrait;
    
    /**
     * Is a link that is probably handled by the router of the frontend framework
     */
    public const TYPE_LINK_PAGE = 'linkPage';
    
    /**
     * Is a link that is on the current site, but should be handled as normal html link.
     */
    public const TYPE_LINK_INTERNAL = 'linkInternal';
    
    /**
     * This link is not on the current host and should probably open in a new tab.
     */
    public const TYPE_LINK_EXTERNAL = 'linkExternal';
    
    /**
     * This is a pseudo element and not a real link.
     * It is only active if the "showSpacer" option is enabled.
     */
    public const TYPE_LINK_SPACER = 'spacer';
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Tsfe\TsfeService
     */
    protected $tsfeService;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformer
     */
    protected $autoTransformer;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * The given, validated options for the menu to render
     *
     * @var array
     */
    protected $options;
    
    /**
     * The menu processor to generate the menu array with.
     * This can be overwritten by implementations
     *
     * @var string
     */
    protected $processorClass = ExtendedMenuProcessor::class;
    
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        TsfeService $tsfeService,
        AutoTransformer $autoTransformer,
        TypoContext $typoContext
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->tsfeService = $tsfeService;
        $this->autoTransformer = $autoTransformer;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Should return a list of menu specific option definitions to apply to the given options
     *
     * @return array
     */
    abstract protected function getMenuOptionDefinition(): array;
    
    /**
     * Receives the default menu typo script definition and should add additional configuration if required.
     * The result must be the adjusted ts config
     *
     * @param   array  $defaultDefinition  The default typo script configuration
     *
     * @return array
     */
    abstract protected function getMenuTsConfig(array $defaultDefinition): array;
    
    /**
     * Renders the menu based on the given options as array
     *
     * @param   array  $options  The options provided for the menu.
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayException
     */
    public function render(array $options): array
    {
        $this->options = $this->validateMenuOptions($options, $this->getMenuOptionDefinition());
        
        $tsConfig = $this->getMenuTsConfig($this->getDefaultMenuTsConfig());
        
        if (! empty($this->options['preProcessor'])) {
            $tsConfig = NamingUtil::resolveCallable($this->options['preProcessor'])
            ($tsConfig, $this->options, static::class);
        }
        
        PageMenuItemDataProcessor::$cacheTags = [];
        
        $result = $this->makeInstance($this->processorClass)
                       ->process($this->tsfeService->getContentObjectRenderer(), [], $tsConfig, []);
        $result = PageMenuItemDataProcessor::removeInvalidMarkers($result['menu']);
        
        if (! empty(PageMenuItemDataProcessor::$cacheTags)) {
            $this->runInCacheScope(static function (Scope $scope): void {
                $scope->addCacheTags(PageMenuItemDataProcessor::$cacheTags);
            });
        }
        
        if (! empty($this->options['postProcessor'])) {
            return NamingUtil::resolveCallable($this->options['postProcessor'])
            ($result, $this->options, static::class);
        }
        
        return $result;
    }
    
    /**
     * Validates the given menu options based on the default and given option definitions
     *
     * @param   array  $options
     * @param   array  $additionalOptionDefinition
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayException
     */
    protected function validateMenuOptions(array $options, array $additionalOptionDefinition = []): array
    {
        $definition = $this->getDefaultMenuOptionDefinition();
        $definition = Arrays::merge($definition, $additionalOptionDefinition, 'allowRemoval');
        
        return Options::make($options, $definition);
    }
    
    /**
     * Generates the typoScript definition of the menu to render based on the current type and options
     *
     * @return array
     */
    protected function getDefaultMenuTsConfig(): array
    {
        // Prepare the basic definition
        $config = [
            'entryLevel' => $this->options['entryLevel'],
            'excludeUidList' => implode(',', $this->options['excludeUidList']),
            'includeNotInMenu' => $this->options['includeNotInMenu'],
            'includeSpacer' => $this->options['showSpacers'],
            'as' => 'menu',
            'expandAll' => '1',
            'titleField' => 'nav_title // title',
            'dataProcessing.' => [],
            'levels' => $this->options['levels'],
        ];
        
        // Build file field definition
        $processorCount = 0;
        if (! empty($this->options['fileFields'])) {
            foreach ($this->options['fileFields'] as $fileField) {
                $config['dataProcessing.'][++$processorCount] = FilesProcessor::class;
                $config['dataProcessing.'][$processorCount . '.'] = [
                    'references.' => [
                        'fieldName' => $fileField,
                    ],
                    'as' => 'fileField.' . $fileField,
                ];
            }
        }
        
        // Add item processor
        $config['dataProcessing.'][++$processorCount] = PageMenuItemDataProcessor::class;
        $config['dataProcessing.'][$processorCount . '.'] = [
            'type' => static::class,
            'options' => $this->options,
            'additionalFields' => $this->options['additionalFields'],
            'fileFields' => $this->options['fileFields'],
            'autoTransformer' => $this->autoTransformer,
            'postProcessor' => empty($this->options['itemPostProcessor']) ? null
                : NamingUtil::resolveCallable($this->options['itemPostProcessor']),
        ];
        
        return $config;
    }
    
    /**
     * Provides the default menu option definition
     *
     * @return array[]
     */
    protected function getDefaultMenuOptionDefinition(): array
    {
        $callableDef = ['default' => null];
        
        return [
            'entryLevel' => [
                'type' => 'int',
                'default' => 0,
            ],
            'excludeUidList' => [
                'type' => 'array',
                'default' => [],
                'preFilter' => function ($v) {
                    return $this->convertPids($v);
                },
            ],
            'includeNotInMenu' => [
                'type' => 'bool',
                'default' => false,
            ],
            'levels' => [
                'type' => 'int',
                'default' => 2,
            ],
            'additionalFields' => [
                'type' => 'array',
                'default' => [],
            ],
            'fileFields' => [
                'type' => 'array',
                'default' => [],
            ],
            'preProcessor' => $callableDef,
            'postProcessor' => $callableDef,
            'itemPostProcessor' => $callableDef,
        ];
    }
    
    
    /**
     * Internal helper to pre-filter the pid's in option values
     *
     * @param   mixed  $pids
     *
     * @return mixed
     */
    protected function convertPids($pids)
    {
        $pidAspect = $this->typoContext->pid();
        
        if (is_string($pids) && $pidAspect->has($pids)) {
            return $pidAspect->get($pids);
        }
        
        if (is_array($pids)) {
            foreach ($pids as $k => $pid) {
                if (is_string($pid) && $pidAspect->has($pid)) {
                    $pids[$k] = $pidAspect->get($pid);
                }
            }
        }
        
        return $pids;
    }
}
