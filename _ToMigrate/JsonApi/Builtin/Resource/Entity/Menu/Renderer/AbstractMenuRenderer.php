<?php
/*
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
 * Last modified: 2020.09.29 at 15:38
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer;


use LaborDigital\Typo3FrontendApi\Event\SiteMenuPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuPreProcessorEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\ExtendedMenuProcessor;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\PageMenuItemDataProcessor;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuItemPostProcessorInterface;
use LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuPostProcessorInterface;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Options\Options;
use TYPO3\CMS\Frontend\DataProcessing\FilesProcessor;

abstract class AbstractMenuRenderer
{
    use FrontendApiContextAwareTrait;

    /**
     * The unique key of the menu to render
     *
     * @var string
     */
    protected $key;

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
     * @param   string  $key      The unique key for this menu to render
     * @param   array   $options  The options provided for the menu.
     *
     * @return array
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     * @throws \Neunerlei\Arrays\ArrayException
     */
    public function render(string $key, array $options): array
    {
        $this->key     = $key;
        $this->options = $this->validateMenuOptions($options, $this->getMenuOptionDefinition());

        $tsConfig = $this->getMenuTsConfig($this->getDefaultMenuTsConfig());
        $tsConfig = $this->FrontendApiContext()->EventBus()->dispatch(
            new SiteMenuPreProcessorEvent($tsConfig, static::class, $this->key, $this->options)
        )->getDefinition();

        $context   = $this->FrontendApiContext();
        $processor = $context->getInstanceOf($this->processorClass);
        $result    = $processor->process($context->Tsfe()->getContentObjectRenderer(), [], $tsConfig, []);
        $result    = PageMenuItemDataProcessor::removeInvalidMarkers($result['menu']);

        return $this->runPostProcessing($result);
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
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    protected function getDefaultMenuTsConfig(): array
    {
        // Prepare the basic definition
        $config = [
            'entryLevel'       => $this->options['entryLevel'],
            'excludeUidList'   => implode(',', $this->options['excludeUidList']),
            'includeNotInMenu' => $this->options['includeNotInMenu'],
            'includeSpacer'    => $this->options['showSpacers'],
            'as'               => 'menu',
            'expandAll'        => '1',
            'titleField'       => 'nav_title // title',
            'dataProcessing.'  => [],
            'levels'           => $this->options['levels'],
        ];

        // Build file field definition
        $processorCount = 0;
        if (! empty($this->options['fileFields'])) {
            foreach ($this->options['fileFields'] as $fileField) {
                $config['dataProcessing.'][++$processorCount]     = FilesProcessor::class;
                $config['dataProcessing.'][$processorCount . '.'] = [
                    'references.' => [
                        'fieldName' => $fileField,
                    ],
                    'as'          => 'fileField.' . $fileField,
                ];
            }
        }

        // Add item processor
        $config['dataProcessing.'][++$processorCount]     = PageMenuItemDataProcessor::class;
        $config['dataProcessing.'][$processorCount . '.'] = [
            'key'              => $this->key,
            'type'             => static::class,
            'options'          => $this->options,
            'additionalFields' => $this->options['additionalFields'],
            'fileFields'       => $this->options['fileFields'],
            'context'          => $this->FrontendApiContext(),
            'postProcessor'    => empty($this->options['itemPostProcessor']) ? null
                : $this->FrontendApiContext()->getInstanceOf($this->options['itemPostProcessor']),
        ];

        return $config;
    }

    /**
     * Internal helper to allow the event based post processing to occur.
     *
     * @param   array  $menu  The generated menu array
     *
     * @return array
     */
    protected function runPostProcessing(array $menu): array
    {
        $context = $this->FrontendApiContext();

        // Check if we have a post processor
        if (! empty($this->options['postProcessor']) && class_exists($this->options['postProcessor'])) {
            /** @var \LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuPostProcessorInterface $processor */
            $processor = $context->getInstanceOf($this->options['postProcessor']);
            $menu      = $processor->process($this->key, $menu, $this->options, static::class);
        }

        // Allow event based processing
        return $context->EventBus()->dispatch(
            new SiteMenuPostProcessorEvent($this->key, $menu, static::class, $this->options)
        )->getMenu();
    }

    /**
     * Provides the default menu option definition
     *
     * @return array[]
     */
    protected function getDefaultMenuOptionDefinition(): array
    {
        return [
            'loadForLayouts'    => null,
            'cacheBasedOnQuery' => null,
            'entryLevel'        => [
                'type'    => 'int',
                'default' => 0,
            ],
            'excludeUidList'    => [
                'type'      => 'array',
                'default'   => [],
                'preFilter' => function ($v) {
                    return $this->convertPids($v);
                },
            ],
            'includeNotInMenu'  => [
                'type'    => 'bool',
                'default' => false,
            ],
            'levels'            => [
                'type'    => 'int',
                'default' => 2,
            ],
            'additionalFields'  => [
                'type'    => 'array',
                'default' => [],
            ],
            'fileFields'        => [
                'type'    => 'array',
                'default' => [],
            ],
            'useV10Renderer'    => [
                'type'    => 'bool',
                'default' => false,
            ],
            'postProcessor'     => [
                'type'      => ['string', 'null'],
                'default'   => null,
                'validator' => static function (?string $class) {
                    if ($class === null) {
                        return true;
                    }
                    if (! class_exists($class)) {
                        return 'The given post processor class: "' . $class . '" does not exist!';
                    }
                    if (! in_array(PageMenuPostProcessorInterface::class, class_implements($class), true)) {
                        return 'The given post processor "' . $class . '" must implement the required interface: ' .
                               PageMenuPostProcessorInterface::class;
                    }

                    return true;
                },
            ],
            'itemPostProcessor' => [
                'type'      => ['string', 'null'],
                'default'   => null,
                'validator' => static function (?string $class) {
                    if ($class === null) {
                        return true;
                    }
                    if (! class_exists($class)) {
                        return 'The given item post processor class: "' . $class . '" does not exist!';
                    }
                    if (! in_array(PageMenuItemPostProcessorInterface::class, class_implements($class), true)) {
                        return 'The given item post processor "' . $class . '" must implement the required interface: ' .
                               PageMenuItemPostProcessorInterface::class;
                    }

                    return true;
                },
            ],
        ];
    }


    /**
     * Internal helper to pre-filter the pid's in option values
     *
     * @param $pids
     *
     * @return array|mixed|string
     */
    protected function convertPids($pids)
    {
        $pidAspect = $this->FrontendApiContext()->TypoContext()->Pid();
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
