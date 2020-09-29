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
 * Last modified: 2020.09.25 at 12:17
 */

declare(strict_types=1);
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
 * Last modified: 2019.09.23 at 06:16
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu;


use LaborDigital\Typo3BetterApi\Tsfe\TsfeService;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuPreProcessorEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use Neunerlei\Arrays\Arrays;
use TYPO3\CMS\Frontend\DataProcessing\FilesProcessor;
use TYPO3\CMS\Frontend\DataProcessing\MenuProcessor;

class PageMenu implements SelfTransformingInterface
{
    use FrontendApiContextAwareTrait;
    use PageMenuDeprecationTrait;

    public const TYPE_MENU_ROOT_LINE = 'rootLineMenu';
    public const TYPE_MENU_PAGE      = 'pageMenu';
    public const TYPE_MENU_DIRECTORY = 'dirMenu';

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
     * The key that was given for this menu/common element
     *
     * @var string
     */
    protected $key;

    /**
     * The value of one of the TYPE_MENU_ constants, defining the type of menu to render
     *
     * @var string
     */
    protected $type;

    /**
     * The options that are defining how the menu should look like
     *
     * @var array
     */
    protected $options;

    /**
     * PageMenu constructor.
     *
     * @param   string  $key      The key that was given for this menu/common element
     * @param   string  $type     The value of one of the TYPE_MENU_ constants
     * @param   array   $options  The options that are defining how the menu should look like
     */
    public function __construct(string $key, string $type, array $options)
    {
        $this->key     = $key;
        $this->type    = $type;
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $context = $this->FrontendApiContext();
        // Handle legacy definitions
        // @todo remove this in v10
        if ($this->options['useV10Renderer'] !== true) {
            switch ($this->type) {
                case static::TYPE_MENU_PAGE:
                    return $this->renderLegacyPageMenu();
                case static::TYPE_MENU_DIRECTORY:
                    return $this->renderLegacyDirectoryMenu();
                case static::TYPE_MENU_ROOT_LINE:
                    return $this->renderLegacyRootLineMenu();
                default:
                    throw new JsonApiException('The menu is not configured correctly! There is no menu type: ' . $this->type);
            }
        }

        // Render the menu
        $definition = $this->makeMenuDefinition();
        $processor  = $context->getInstanceOf(MenuProcessor::class);
        $result     = $processor->process($context->getSingletonOf(TsfeService::class)->getContentObjectRenderer(), [], $definition, []);
        $result     = $result['menu'];

        return $this->runPostProcessing($result);
    }

    /**
     * Generates the typoScript definition of the menu to render based on the current type and options
     *
     * @return array
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    protected function makeMenuDefinition(): array
    {
        // Prepare the basic definition
        $definition = [
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

        // Build the type based definition
        switch ($this->type) {
            case static::TYPE_MENU_PAGE:
                $typeDefinition = [
                    'special'  => 'list',
                    'special.' => [
                        'value.' => [
                            'field' => 'pages',
                        ],
                    ],
                ];
                break;
            case static::TYPE_MENU_DIRECTORY:
                $typeDefinition = [
                    'special'  => 'directory',
                    'special.' => [
                        'value' => $this->options['pid'],
                    ],
                ];
                break;
            case static::TYPE_MENU_ROOT_LINE:
                $typeDefinition = [
                    'entryLevel' => $this->options['entryLevel'],
                    'special'    => 'rootline',
                    'special.'   => [
                        'range' => $this->options['offsetStart'] . '|' . (empty($this->options['offsetEnd']) ? '999' : (-abs($this->options['offsetEnd']) - 1)),
                    ],
                ];
                break;
            default:
                throw new JsonApiException('The menu is not configured correctly! There is no menu type: ' . $this->type);
        }

        // Build file field definition
        $processorCount = 0;
        if (! empty($this->options['fileFields'])) {
            foreach ($this->options['fileFields'] as $fileField) {
                $definition['dataProcessing.'][++$processorCount]     = FilesProcessor::class;
                $definition['dataProcessing.'][$processorCount . '.'] = [
                    'references.' => [
                        'fieldName' => $fileField,
                    ],
                    'as'          => 'fileField.' . $fileField,
                ];
            }
        }

        // Add item processor
        $definition['dataProcessing.'][++$processorCount]     = PageMenuItemDataProcessor::class;
        $definition['dataProcessing.'][$processorCount . '.'] = [
            'key'              => $this->key,
            'type'             => $this->type,
            'options'          => $this->options,
            'additionalFields' => $this->options['additionalFields'],
            'fileFields'       => $this->options['fileFields'],
            'context'          => $this->FrontendApiContext(),
            'postProcessor'    => empty($this->options['itemPostProcessor']) ? null
                : $this->FrontendApiContext()->getInstanceOf($this->options['itemPostProcessor']),
        ];

        // Build the final definition
        $definition = Arrays::merge($definition, $typeDefinition);

        return $this->FrontendApiContext()->EventBus()->dispatch(
            new SiteMenuPreProcessorEvent($definition, $this->type, $this->key, $this->options)
        )->getDefinition();
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
            $menu      = $processor->process($this->key, $menu, $this->options, $this->type);
        }

        // Allow event based processing
        return $context->EventBus()->dispatch(
            new SiteMenuPostProcessorEvent($this->key, $menu, $this->type, $this->options)
        )->getMenu();
    }
}
