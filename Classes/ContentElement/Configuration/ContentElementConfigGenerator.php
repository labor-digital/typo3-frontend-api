<?php
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
 * Last modified: 2019.08.27 at 12:31
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration;


use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\Helper\CTypeRegistrationTrait;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\CachedStackGeneratorInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\Table\ExtBasePersistenceMapperTrait;
use LaborDigital\Typo3FrontendApi\ContentElement\ContentElementHandler;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use LaborDigital\Typo3FrontendApi\Event\ContentElementTableDefinitionFilterEvent;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

class ContentElementConfigGenerator implements CachedStackGeneratorInterface
{
    use ExtBasePersistenceMapperTrait;
    use CTypeRegistrationTrait;

    /**
     * @inheritDoc
     */
    public function generate(array $stack, ExtConfigContext $context, array $additionalArguments, $option)
    {
        // Load the tt_content tca table
        $tcaTable = TcaTable::makeInstance('tt_content', $context);

        // Prepare temporary storage
        $tmp = new class {
            public $typoScript                = [];
            public $columnMapTypoScript       = [];
            public $configurators             = [];
            public $dataHandlerActionHandlers = [];
            public $tsConfig                  = [];
            public $iconDefinitionArgs        = [];
            public $backendPreviewRenderers   = [];
            public $backendListLabelRenderers = [];
            public $cTypeEntries              = [];
        };

        // Flush sql builder
        $context->SqlGenerator->flush();

        // Loop through the element stack
        foreach ($stack as $elementName => $data) {
            $context->runWithFirstCachedValueDataScope($data, function () use (
                $data,
                $context,
                $elementName,
                $tcaTable,
                $tmp,
                $additionalArguments
            ) {
                // Create the configurator
                $configurator         = $context->getInstanceOf(ContentElementConfigurator::class,
                    [$elementName, $context, $tcaTable, $additionalArguments['defaultType']]);
                $tmp->configurators[] = $configurator;

                // Run the stack for this element
                $context->runWithCachedValueDataScope($data, static function (string $class) use ($context, $configurator) {
                    // Load the config class
                    if (! in_array(ContentElementConfigurationInterface::class, class_implements($class), true)) {
                        throw new ContentElementConfigException(
                            'The registered element configuration: ' . $class . ' does not implement the required interface: ' .
                            ContentElementConfigurationInterface::class
                        );
                    }

                    // Check if we should use this class as controller
                    if (empty($configurator->getControllerClass()) &&
                        in_array(ContentElementControllerInterface::class, class_implements($class), true)) {
                        $configurator->setControllerClass($class);
                    }

                    // Run the configuration
                    call_user_func([$class, 'configureElement'], $configurator, $context);
                });

                // Prepare the type tca
                $configurator->getForm()->__renameVirtualColumns();

                // Make parts
                $tmp->typoScript[]                = $this->makeTypoScript($configurator, $context);
                $tmp->dataHandlerActionHandlers   = Arrays::attach($tmp->dataHandlerActionHandlers, $configurator->__getDataHandlerActionHandlers());
                $tmp->backendListLabelRenderers[] = $this->makeRegisterBackendLabelListRendererArgs($configurator);
                if ($configurator->renderBackendPreview()) {
                    $tmp->backendPreviewRenderers[] = $this->makeRegisterBackendPreviewRendererArgs($configurator);
                }

                // Make those parts only if the element does not replace an existing cType
                // in that case we don't need to register an additional element
                $isReplacementForExistingElement = $configurator->getExistingElementCType() !== null;
                if (! $isReplacementForExistingElement) {
                    $tmp->cTypeEntries[]       = $this->makeRegisterCTypeElement($configurator, $context);
                    $tmp->iconDefinitionArgs[] = $this->makeIconDefinitionArgs($configurator, $context);
                    $tmp->tsConfig[]           = $this->makeTsConfig($configurator, $context);
                }

            });
        }

        // Allow filtering
        $context->EventBus()->dispatch(new ContentElementTableDefinitionFilterEvent(
            $tcaTable, $context, $tmp->configurators
        ));

        // Inject the cType definitions into the tca table
        $tca = $tcaTable->__build();
        $ref = ['tt_content' => &$tca];
        $this->registerCTypesForElements($ref, $tmp->cTypeEntries);

        // Build config
        $config                            = $context->getInstanceOf(ContentElementConfig::class);
        $config->typoScript                = $this->makeFinalTypoScript($tmp->typoScript);
        $config->ttContentTca              = $tca;
        $config->dataHandlerActionHandlers = $tmp->dataHandlerActionHandlers;
        $config->iconDefinitionArgs        = $tmp->iconDefinitionArgs;
        $config->tsConfig                  = implode(PHP_EOL . PHP_EOL, $tmp->tsConfig);
        $config->backendPreviewRenderers   = array_filter($tmp->backendPreviewRenderers);
        $config->backendListLabelRenderers = array_filter($tmp->backendListLabelRenderers);

        // Build sql config
        $sqlGenerator = $context->SqlGenerator();
        $sqlGenerator->removeTableDefinitions('tt_content');
        $config->sql = $sqlGenerator->getFullSql();

        // Build the virtual columns
        $virtualColumns = [];
        foreach ($tmp->configurators as $configurator) {
            /** @var \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator $configurator */
            $virtualColumnsLocal                           = $configurator->getForm()->__getVirtualColumns();
            $virtualColumns[$configurator->getSignature()] = $virtualColumnsLocal;
            $tmp->columnMapTypoScript[]                    = $this->makeColumnMap($virtualColumnsLocal, $configurator);
        }
        $virtualColumns         = array_filter($virtualColumns);
        $config->virtualColumns = $virtualColumns;
        $config->typoScript     .= implode(PHP_EOL, $tmp->columnMapTypoScript);
        unset($tmp);

        // Done
        return $config;
    }

    /**
     * Generates the typoscript definition for a single content element
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     * @param   ExtConfigContext                                                                        $context
     *
     * @return string
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException
     */
    protected function makeTypoScript(ContentElementConfigurator $configurator, ExtConfigContext $context): string
    {
        // Check if we got a controller class
        if (empty($configurator->getControllerClass())) {
            throw new ContentElementConfigException('There is no controller class registered for content element: ' . $configurator->getSignature());
        }

        // Select content object to use
        $co = $configurator->isCacheEnabled() ? 'USER' : 'USER_INT';

        // Build definition
        $userFunc   = ContentElementHandler::class . '->handleFrontend';
        $type       = Inflector::toCamelCase($context->getExtKey()) . '/' . Inflector::toCamelCase($configurator->getElementName());
        $cssClasses = implode(', ', $configurator->getCssClasses());

        return "
		{$configurator->getSignature()} = $co
		{$configurator->getSignature()} {
			userFunc = $userFunc
			controllerClass = {$configurator->getControllerClass()}
			modelClass = {$configurator->getModelClass()}
			cssClasses = {$cssClasses}
			type = {$type}
			extKey = {$context->getExtKey()}
			vendor = {$context->getVendor()}
			caching {
			    ttl = {$configurator->getCacheTtl()}
			    enabled = {$configurator->isCacheEnabled()}
			    tags {
			        " .
               (static function () use ($configurator) {
                   $result = [];
                   foreach ($configurator->getCacheTags() as $i => $tag) {
                       $result[] = $i . ' = ' . $tag;
                   }

                   return implode(PHP_EOL, $result);
               })() . '
			    }
			}
		}';
    }

    /**
     * Internal helper to build the typoScript that is required to map our virtual columns to less cryptic properties
     *
     * @param   array                                                                                   $virtualColumns
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     *
     * @return string
     */
    protected function makeColumnMap(array $virtualColumns, ContentElementConfigurator $configurator): string
    {
        if (empty($configurator->getModelClass())) {
            return '';
        }

        // Convert the column names into property names
        $vColPropertyMap = array_flip($virtualColumns);
        $vColPropertyMap = array_map(function (string $column) {
            return Inflector::toProperty($column);
        }, $vColPropertyMap);

        return $this->getPersistenceTs([$configurator->getModelClass()], 'tt_content', [$configurator->getModelClass() => $vColPropertyMap]);
    }

    /**
     * Combines the single typoScript definitions into a single typo script string
     *
     * @param   array  $ts
     *
     * @return string
     */
    protected function makeFinalTypoScript(array $ts): string
    {
        return '
	tt_content {'
               . implode(PHP_EOL . PHP_EOL, $ts) . '
	}';
    }

    /**
     * Adds a new element entry for a cType that will be registered for this content element
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext                                 $context
     *
     * @return array
     */
    protected function makeRegisterCTypeElement(ContentElementConfigurator $configurator, ExtConfigContext $context): array
    {
        $sectionLabel = $configurator->getCTypeSection();
        if (empty($sectionLabel)) {
            $sectionLabel = Inflector::toHuman($context->getExtKey());
        }

        return [
            $sectionLabel,
            $configurator->getTitle(),
            $configurator->getSignature(),
            $configurator->getIcon(),
        ];
    }

    /**
     * Returns the arguments that have to be used to register the element's icon in the icon registry
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext                                 $context
     *
     * @return array
     * @see IconRegistry::registerIcon();
     */
    protected function makeIconDefinitionArgs(ContentElementConfigurator $configurator, ExtConfigContext $context): array
    {
        $iconExtension = strtolower(pathinfo($configurator->getIcon(), PATHINFO_EXTENSION));

        return array_values([
            'identifier'            => $this->makeIconIdentifier($configurator, $context),
            'iconProviderClassName' => $iconExtension === 'svg' ? SvgIconProvider::class : BitmapIconProvider::class,
            'options'               => ['source' => $configurator->getIcon()],
        ]);
    }

    /**
     * Builds the ts config script that is required to register a new content element wizard icon for this plugin
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext                                 $context
     *
     * @return string
     */
    protected function makeTsConfig(ContentElementConfigurator $configurator, ExtConfigContext $context): string
    {
        if ($configurator->getWizardTab() === false) {
            return '';
        }

        $header = ! empty($configurator->getWizardTabLabel()) ? 'header = ' . $configurator->getWizardTabLabel() : '';

        return "
		mod.wizards.newContentElement.wizardItems.{$configurator->getWizardTab()} {
			$header
			elements {
				{$configurator->getSignature()} {
					iconIdentifier = {$this->makeIconIdentifier($configurator, $context)}
					title = {$configurator->getTitle()}
					description = {$configurator->getDescription()}
					tt_content_defValues {
						CType = {$configurator->getSignature()}
					}
				}
			}
			show := addToList({$configurator->getSignature()})
		}
		";
    }

    /**
     * Builds the arguments that have to be passed to BackendPreviewService::registerBackendPreviewRenderer to register
     * the backend preview renderer for this content element. Null is returned if there is no preview renderer
     * registered
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     *
     * @return array|null
     */
    protected function makeRegisterBackendPreviewRendererArgs(ContentElementConfigurator $configurator): ?array
    {
        return array_values([
            "rendererClass"    => ContentElementHandler::class,
            "fieldConstraints" => ["CType" => $configurator->getSignature()],
            "override"         => true,
        ]);
    }

    /**
     * Builds the arguments that have to be passed to BackendPreviewService::registerBackendListLabelRenderer to
     * register the backend list label renderer for this plugin. Null is returned if there is no renderer registered
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     *
     * @return array|null
     */
    protected function makeRegisterBackendLabelListRendererArgs(ContentElementConfigurator $configurator): ?array
    {
        if (empty($configurator->getBackendListLabelRenderer())) {
            return null;
        }

        return array_values([
            "rendererClassOrColumns" => $configurator->getBackendListLabelRenderer(),
            "fieldConstraints"       => ["CType" => $configurator->getSignature()],
        ]);
    }

    /**
     * Internal helper to create the icon identifier for this content element
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator  $configurator
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext                                 $context
     *
     * @return string
     */
    protected function makeIconIdentifier(ContentElementConfigurator $configurator, ExtConfigContext $context): string
    {
        return Inflector::toDashed("content-element-" . $context->getExtKey() . "-" . $configurator->getElementName());
    }
}
