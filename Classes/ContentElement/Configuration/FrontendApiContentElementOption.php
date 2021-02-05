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
 * Last modified: 2020.01.17 at 13:53
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration;


use LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewServiceInterface;
use LaborDigital\Typo3BetterApi\Event\Events\ExtTablesLoadedEvent;
use LaborDigital\Typo3BetterApi\Event\Events\SqlDefinitionFilterEvent;
use LaborDigital\Typo3BetterApi\Event\Events\TcaCompletelyLoadedEvent;
use LaborDigital\Typo3BetterApi\Event\Events\TcaWithoutOverridesLoadedEvent;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractChildExtConfigOption;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementDataPostProcessorInterface;
use LaborDigital\Typo3FrontendApi\ContentElement\VirtualColumn\VirtualColumnEventHandler;
use LaborDigital\Typo3FrontendApi\Event\ContentElementDefaultTypeFilterEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\Inflection\Inflector;
use Neunerlei\PathUtil\Path;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class FrontendApiContentElementOption extends AbstractChildExtConfigOption
{

    protected const CONTENT_ELEMENT_DEFAULT_TYPE = "frontendApiContentElementDefaultType";

    /**
     * Holds the raw configuration while we collect the options
     *
     * @var array
     */
    protected $config
        = [
            "dataPostProcessors" => [],
            "globalCssClasses"   => [],
        ];

    /**
     * @var \LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewServiceInterface
     */
    protected $lazyBackendPreviewService;

    /**
     * FrontendApiContentElementOption constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewServiceInterface  $lazyBackendPreviewService
     */
    public function __construct(BackendPreviewServiceInterface $lazyBackendPreviewService)
    {
        $this->lazyBackendPreviewService = $lazyBackendPreviewService;
    }

    /**
     * @inheritDoc
     */
    public function subscribeToEvents(EventSubscriptionInterface $subscription)
    {
        $subscription->subscribe(TcaWithoutOverridesLoadedEvent::class, "__applyTca", ["priority" => 100]);
        $subscription->subscribe(TcaCompletelyLoadedEvent::class, "__applyTcaOverrides", ["priority" => 400]);
        $subscription->subscribe(ExtTablesLoadedEvent::class, "__applyExtTables");

        // Register sql extender
        if ($this->context->TypoContext->getEnvAspect()->isInstall()) {
            $subscription->subscribe(SqlDefinitionFilterEvent::class, "__applySqlExtension");
        }
    }

    /**
     * Registers a new content element. Please note that the content elements you register with this
     * option are completely different from the extBase content elements. These content elements are mend for
     * single page applications and are designed for an "API-first" design approach.
     *
     * @param   string       $contentElementConfigClass  The configuration/controller class you want to register
     *                                                   The given class has to implement either the
     *                                                   ContentElementConfigurationInterface or has to extend the
     *                                                   AbstractContentElementController class. We will automatically
     *                                                   strip suffixes like content, element, ext, config, configuration,
     *                                                   controller and override(s) when we generate the content element's
     *                                                   name automatically
     * @param   string|null  $contentElementName         By default the name of the content element will be generated by
     *                                                   the given class name, but you can always set it to something
     *                                                   different using this parameter. By default
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     * @see \LaborDigital\Typo3FrontendApi\ContentElement\Controller\AbstractContentElementController
     * @see \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurationInterface
     */
    public function registerContentElement(string $contentElementConfigClass, ?string $contentElementName = null): FrontendApiContentElementOption
    {
        if (empty($contentElementName)) {
            $contentElementName = $this->makeContentElementNameFromClassName($contentElementConfigClass);
        }

        return $this->addRegistrationToCachedStack("contentElements", $contentElementName, $contentElementConfigClass);
    }

    /**
     * Similar to registerContentElement() but registers a whole directory of classes instead only a single class.
     *
     * @param   string  $directory    The directory path to add the elements from
     * @param   bool    $asOverrides  If this is set to true the elements will be used in the override stack instead of the
     *                                registration
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     * @see \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiOption::registerContentElement
     */
    public function registerContentElementDirectory(
        string $directory = "EXT:{{extKey}}/Classes/Controller/ContentElement",
        bool $asOverrides = false
    ): FrontendApiContentElementOption {
        return $this->addDirectoryToCachedStack("contentElements", $directory, function (string $class) {
            return in_array(ContentElementConfigurationInterface::class, class_implements($class));
        }, function (string $class) {
            return $this->makeContentElementNameFromClassName($class);
        }, $asOverrides);
    }

    /**
     * Can be used to modify another, registered content element.
     *
     * @param   string       $contentElementConfigClass  The configuration override class to extend the content element. We
     *                                                   will automatically strip suffixes like content, element, ext,
     *                                                   config, configuration, controller and override(s)
     * @param   string|null  $contentElementName         Can be used to set the content element's name you target with this
     *                                                   override.
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     * @see \LaborDigital\Typo3FrontendApi\ContentElement\Controller\AbstractContentElementController
     * @see \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurationInterface
     */
    public function registerContentElementOverride(string $contentElementConfigClass, ?string $contentElementName = null): FrontendApiContentElementOption
    {
        if (empty($contentElementName)) {
            $contentElementName = $this->makeContentElementNameFromClassName($contentElementConfigClass);
        }

        return $this->addOverrideToCachedStack("contentElements", $contentElementName, $contentElementConfigClass);
    }

    /**
     * Returns the list of all registered content element data post processor classes
     *
     * @return array
     */
    public function getContentElementDataProcessors(): array
    {
        return $this->config["postProcessors"];
    }

    /**
     * Sets the list of content element data post processor classes as an array of class names.
     *
     * @param   array  $postProcessors
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     * @see ContentElementDataPostProcessorInterface
     */
    public function setContentElementDataProcessors(array $postProcessors): FrontendApiContentElementOption
    {
        $this->config["dataPostProcessors"] = [];
        array_map([$this, "registerContentElementDataPostProcessor"], $postProcessors);

        return $this;
    }

    /**
     * Adds a new content element data post processor. Post processors are called on all content elements we are
     * processing. They are meant to extend the content element's data array with additional information. This comes in
     * quite handy if your have something like recurring fields for, lets say special style classes you want to
     * transfer to all your content elements.
     *
     * The given class has to implement the ContentElementDataPostProcessorInterface interface to work.
     *
     * @param   string  $postProcessor
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     * @see ContentElementDataPostProcessorInterface
     */
    public function registerContentElementDataPostProcessor(string $postProcessor): FrontendApiContentElementOption
    {
        if (in_array($postProcessor, $this->config["dataPostProcessors"])) {
            return $this;
        }
        if (! in_array(ContentElementDataPostProcessorInterface::class, class_implements($postProcessor))) {
            throw new JsonApiException(
                "The given post processor: $postProcessor does not implement the required interface: " .
                ContentElementDataPostProcessorInterface::class
            );
        }
        $this->config["dataPostProcessors"][] = $postProcessor;

        return $this;
    }

    /**
     * Removes a previously registered content element data post processor from the list.
     *
     * @param   string  $postProcessor
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     */
    public function removeContentElementDataPostProcessor(string $postProcessor): FrontendApiContentElementOption
    {
        $k = array_search($postProcessor, $this->config["dataPostProcessors"]);
        if (! is_numeric($k)) {
            return $this;
        }
        unset($this->config["dataPostProcessors"][$k]);

        return $this;
    }

    /**
     * Adds one ore more css classes to the list of global content element classes.
     * The classes you set here will be distributed to every content element that is provided in the frontend.
     * Multiple classes can be defined as a string with a space or a comma as separator.
     *
     * @param $classes
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    public function addGlobalElementCssClasses($classes): FrontendApiContentElementOption
    {
        if (is_string($classes)) {
            $classes = Arrays::makeFromStringList(str_replace(",", " ", $classes), " ");
        }
        if (! is_array($classes)) {
            throw new JsonApiException("Invalid css class list given!");
        }
        $this->config["globalCssClasses"] = array_unique(Arrays::attach($this->config["globalCssClasses"], $classes));

        return $this;
    }

    /**
     * Removes one or multiple classes from the global content element registration.
     * Multiple classes can be defined as a string with a space or a comma as separator.
     *
     * @param $classes
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\FrontendApiContentElementOption
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    public function removeGlobalElementCssClasses($classes): FrontendApiContentElementOption
    {
        if (is_string($classes)) {
            $classes = Arrays::makeFromStringList(str_replace(",", " ", $classes), " ");
        }
        if (! is_array($classes)) {
            throw new JsonApiException("Invalid css class list given!");
        }
        $this->config["globalCssClasses"] = array_diff($this->config["globalCssClasses"], $classes);

        return $this;
    }

    /**
     * Internal helper to fill the main config repository' config array with the local configuration
     *
     * @param   array  $config
     */
    public function __buildConfig(array &$config): void
    {
        $config["contentElement"] = $this->config;
    }

    /**
     * Internal helper to provide the default tca definition for a content element
     */
    public function __applyTca()
    {
        // We register this type to ensure that the content element's can benefit when a tca override registers new elements on all tt_content types.
        $GLOBALS["TCA"]["tt_content"]["types"][static::CONTENT_ELEMENT_DEFAULT_TYPE] = [
            "showitem" => "
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.language,
					--palette--;;language,
					--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
					--palette--;;hidden,
					--palette--;;access,
					--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended,",
        ];

    }

    /**
     * Internal helper to inject the tca configuration for our content elements
     */
    public function __applyTcaOverrides()
    {
        // Extract the default type
        static $defaultType = null;
        if (empty($defaultType)) {
            $defaultType = $GLOBALS["TCA"]["tt_content"]["types"][static::CONTENT_ELEMENT_DEFAULT_TYPE];
        }
        unset($GLOBALS["TCA"]["tt_content"]["types"][static::CONTENT_ELEMENT_DEFAULT_TYPE]);

        // Allow filtering
        $this->context->EventBus->dispatch(($e = new ContentElementDefaultTypeFilterEvent($defaultType)));
        $defaultType = $e->getType();

        // Build the content element config
        /** @var \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfig $elementConfig */
        $elementConfig = $this->runCachedStackGenerator("contentElements", ContentElementConfigGenerator::class, ["defaultType" => $defaultType]);
        $this->context->Fs->setFileContent("frontendApi/contentElementSql.sql", $elementConfig->sql);

        // Register the required typoScript
        $this->context->TypoScript->addSetup($elementConfig->typoScript, [
            'dynKey' => 't3fa.contentElements',
            'dynMemory',
        ]);

        // Update the tt_content TCA
        $GLOBALS["TCA"]["tt_content"]                                       = $elementConfig->ttContentTca;
        $GLOBALS["TCA"]["tt_content"]["additionalConfig"]["virtualColumns"] = $elementConfig->virtualColumns;

        // Store a simplified version of the element config
        $fieldsToKeep = [
            "dataHandlerActionHandlers",
            "tsConfig",
            "iconDefinitionArgs",
            "backendPreviewRenderers",
            "backendListLabelRenderers",
        ];
        foreach (get_object_vars($elementConfig) as $k => $v) {
            if (in_array($k, $fieldsToKeep)) {
                continue;
            }
            $elementConfig->$k = null;
        }
        $this->context->Fs->setFileContent("frontendApi/ContentElementConfig.txt", $elementConfig);

    }

    /**
     * Internal helper to register the configuration for the backend
     */
    public function __applyExtTables()
    {
        if ($this->context->TypoContext->getEnvAspect()->isBackend()) {
            // Add handling for virtual tca columns
            $this->context->DataHandlerActions
                ->registerActionHandler("tt_content", "save", VirtualColumnEventHandler::class, "saveFilter")
                ->registerActionHandler("tt_content", "form", VirtualColumnEventHandler::class, "formFilter");

            // Register event handlers for content element virtual columns
            $this->context->EventBus->addLazySubscriber(VirtualColumnEventHandler::class);

            // Load the element config
            /** @var \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfig $elementConfig */
            $cacheFile = "frontendApi/ContentElementConfig.txt";
            if (! $this->context->Fs->hasFile($cacheFile)) {
                return;
            }
            $elementConfig = $this->context->Fs->getFileContent($cacheFile);
            if (empty($elementConfig)) {
                return;
            }

            // Register data handler action handlers
            foreach ($elementConfig->dataHandlerActionHandlers as $table => $actions) {
                foreach ($actions as $action => $handlers) {
                    foreach ($handlers as $handler) {
                        $this->context->DataHandlerActions->registerActionHandler($table, $action, ...$handler);
                    }
                }
            }

            // Register plugin wizard icons
            ExtensionManagementUtility::addPageTSConfig($elementConfig->tsConfig);
            $iconRegistry = $this->context->getInstanceOf(IconRegistry::class);
            foreach ($elementConfig->iconDefinitionArgs as $args) {
                $iconRegistry->registerIcon(...$args);
            }

            // Register backend preview and label renderers
            foreach ($elementConfig->backendPreviewRenderers as $args) {
                $this->lazyBackendPreviewService->registerBackendPreviewRenderer(...$args);
            }
            foreach ($elementConfig->backendListLabelRenderers as $args) {
                $this->lazyBackendPreviewService->registerBackendListLabelRenderer(...$args);
            }
        }
    }

    /**
     * Internal event handler which is called in the install tool when the sql schema is validated
     * It injects our compiled sql code for typo3 to use
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\SqlDefinitionFilterEvent  $event
     */
    public function __applySqlExtension(SqlDefinitionFilterEvent $event)
    {
        $event->addNewDefinition($this->context->Fs->getFileContent("frontendApi/contentElementSql.sql"));
    }

    /**
     * Internal helper that is used if there was no resource type name given.
     * In that case we will use the config class as naming base and try to extract the plugin name out of it.
     *
     * We will automatically strip suffixes like content, element, ext, config, configuration, controller and
     * override(s) from the base name before we convert it into a plugin name
     *
     * @param   string  $configClass
     *
     * @return string
     */
    protected function makeContentElementNameFromClassName(string $configClass): string
    {
        $baseName = Path::classBasename($configClass);
        $baseName = preg_replace("~(content)?(element)?(ext)?(config|configuration|controller)?(overrides?)?$~si", "", $baseName);

        return Inflector::toCamelBack($baseName);
    }
}
