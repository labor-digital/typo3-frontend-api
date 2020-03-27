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
 * Last modified: 2019.08.27 at 11:40
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration;


use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable;
use LaborDigital\Typo3BetterApi\BackendPreview\BackendListLabelRendererInterface;
use LaborDigital\Typo3BetterApi\DataHandler\DataHandlerActionCollectorTrait;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\NamingConvention\Naming;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\Table\ContentElementForm;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\DefaultContentElementModel;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;

class ContentElementConfigurator {
	use DataHandlerActionCollectorTrait;
	
	/**
	 * The given element name for this content element
	 * @var string
	 */
	protected $elementName;
	
	/**
	 * The given tca table for the tt_content type definition
	 * @var TcaTable
	 */
	protected $table;
	
	/**
	 * The form element that is used to configure this content element
	 * @var ContentElementForm
	 */
	protected $form;
	
	/**
	 * The extension key to register this content element with
	 * @var string
	 */
	protected $extKey;
	
	/**
	 * The context to create the content element with
	 * @var ExtConfigContext
	 */
	protected $context;
	
	/**
	 * The typoScript signature of this element
	 * @var string
	 */
	protected $signature;
	
	/**
	 * The visible name of the contentElement. Should be translation! If not set, the humanized extension key and
	 * contentElement name are used.
	 * @var string
	 */
	protected $title = "";
	
	/**
	 * The visible description of this contentElement in the new content element wizard
	 * @var string
	 */
	protected $description = "";
	
	/**
	 * The id of the new content element wizard tab. "common elements" by default.
	 * Setting this value to FALSE (bool) will disable the creation of a wizard entry for this element
	 * @var string|bool
	 */
	protected $wizardTab = "common";
	
	/**
	 * Can be used to define the label of a certain wizard tab.
	 * This can be used if you create a new wizard tab by using the $wizardTab option
	 * @var string|null
	 */
	protected $wizardTabLabel;
	
	/**
	 * Defines if the frontend output of this content element is cached or not
	 * @var bool
	 */
	protected $useCache = TRUE;
	
	/**
	 * Optional path like EXT:extkey... to a icon for this element.
	 * If not given the ext_icon.gif in the root directory will be used.
	 * @var string
	 */
	protected $icon;
	
	/**
	 * The class name of the element's controller
	 * @var string
	 */
	protected $controllerClass;
	
	/**
	 * The model class that should be used for this content element's data
	 * @var string
	 */
	protected $modelClass;
	
	/**
	 * True if we should render a backend preview for this content element
	 * @var bool
	 */
	protected $backendPreview = TRUE;
	
	/**
	 * The cType of the element we should
	 * @var string|null
	 */
	protected $existingElementCType;
	
	/**
	 * The default type of the content element form
	 * @var array
	 */
	protected $defaultType;
	
	/**
	 * The list of css classes that should be provided for this content element
	 * @var array
	 */
	protected $cssClasses = [];
	
	/**
	 * The section label of this element when it is rendered in the cType select box
	 * @var string
	 */
	protected $cTypeSection;
	
	/**
	 * The class that is responsible for rendering the backend list label for this plugin
	 * @var string|null
	 */
	protected $backendListLabelRenderer;
	
	/**
	 * True when the backend list label renderer was set -> meaning we should keep the value, even if the controller
	 * changes...
	 * @var bool
	 */
	protected $backendListLabelRendererWasSet = FALSE;
	
	/**
	 * ContentElementConfigurator constructor.
	 *
	 * @param string                                                      $elementName
	 * @param \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext     $context
	 * @param \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable $table
	 * @param array                                                       $defaultType
	 */
	public function __construct(string $elementName, ExtConfigContext $context, TcaTable $table, array $defaultType) {
		$this->context = $context;
		$this->icon = "EXT:" . $context->getExtKey() . "/ext_icon.gif";
		$this->elementName = $elementName;
		$this->table = $table;
		$this->title = Inflector::toHuman($this->context->getExtKey()) . ": " . Inflector::toHuman($this->elementName);
		$this->signature = Naming::pluginSignature($this->elementName, $this->context->getExtKey());
		$this->defaultType = $defaultType;
		
		// Check if the model class exists
		$modelClass = $this->makeModelClassName($elementName);
		$this->modelClass = DefaultContentElementModel::class;
		if (class_exists($modelClass) && in_array(AbstractContentElementModel::class, class_parents($modelClass)))
			$this->modelClass = $modelClass;
	}
	
	/**
	 * Returns the given element name for this content element
	 * @return string
	 */
	public function getElementName(): string {
		return $this->elementName;
	}
	
	/**
	 * Returns the typoScript signature of this element
	 * @return string
	 */
	public function getSignature(): string {
		if ($this->isUsedForExistingElement()) return $this->getExistingElementCType();
		return $this->signature;
	}
	
	/**
	 * Sets the visible name of the content element. Should be translation! If not set,
	 * the humanized extension key and content element name  are used.
	 *
	 * @param string $title
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator
	 */
	public function setTitle(string $title): ContentElementConfigurator {
		$this->title = $title;
		return $this;
	}
	
	/**
	 * Returns the visible name of the content element.
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}
	
	/**
	 * Returns the currently set section label of this element when it is rendered in the cType select box
	 * @return string
	 */
	public function getCTypeSection(): string {
		return (string)$this->cTypeSection;
	}
	
	/**
	 * Is used to set the section label of this element when it is rendered in the cType select box.
	 * If this is not defined, a label is automatically generated using the extension key
	 *
	 * @param string $cTypeSection
	 *
	 * @return ContentElementConfigurator
	 */
	public function setCTypeSection(string $cTypeSection): ContentElementConfigurator {
		$this->cTypeSection = $cTypeSection;
		return $this;
	}
	
	/**
	 * This option can be used if you want this element to overwrite a TYPO3 content element, like "text", "header" or
	 * "table". This content element's controller will be used to overwrite the default rendering definition. Please
	 * note that if you use this option the title, description and wizard configurations are not used as we will
	 * replace the existing rendering definition with our own
	 *
	 * @param string $cType
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator
	 */
	public function useForExistingElement(?string $cType): ContentElementConfigurator {
		$this->existingElementCType = $cType;
		// Reset the form
		$this->form = NULL;
		return $this;
	}
	
	/**
	 * Returns the element's cType we should use this element's rendering definition for.
	 *
	 * @return string|null
	 * @see \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator::useForExistingElement()
	 */
	public function getExistingElementCType(): ?string {
		return $this->existingElementCType;
	}
	
	/**
	 * Returns true if this content element is used for an existing element's cType
	 *
	 * @return bool
	 */
	public function isUsedForExistingElement(): bool {
		return $this->getExistingElementCType() !== NULL;
	}
	
	/**
	 * Returns the visible description of this content element in the new content element wizard
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}
	
	/**
	 * Sets the visible description of this content element in the new content element wizard
	 *
	 * @param string $description
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator
	 */
	public function setDescription(string $description): ContentElementConfigurator {
		$this->description = $description;
		return $this;
	}
	
	/**
	 * Returns the id of the new content element wizard tab. "common" by default.
	 * If false is returned the wizard tab should not be created
	 * @return bool|string
	 */
	public function getWizardTab() {
		return $this->wizardTab;
	}
	
	/**
	 * Used to set the id of the new content element wizard tab. "common" by default.
	 * Setting this value to FALSE (bool) will disable the creation of a wizard entry for this element
	 *
	 * @param bool|string $wizardTab
	 *
	 * @return ContentElementConfigurator
	 */
	public function setWizardTab($wizardTab) {
		$this->wizardTab = $wizardTab;
		return $this;
	}
	
	/**
	 * Returns the currently set label for this wizard tab or null
	 * @return string|null
	 */
	public function getWizardTabLabel(): ?string {
		return $this->wizardTabLabel;
	}
	
	/**
	 * Can be used to define the label of a certain wizard tab.
	 * This can be used if you create a new wizard tab by using the $wizardTab option
	 *
	 * @param string|null $wizardTabLabel
	 *
	 * @return ContentElementConfigurator
	 */
	public function setWizardTabLabel(?string $wizardTabLabel): ContentElementConfigurator {
		$this->wizardTabLabel = $wizardTabLabel;
		return $this;
	}
	
	/**
	 * Returns the class name of the element's controller
	 * Returns null if there is currently no controller class registered
	 * @return string|null
	 */
	public function getControllerClass(): ?string {
		return $this->controllerClass;
	}
	
	/**
	 * Is used to set the controller class of this content element.
	 * If the configuration class also has the content element controller interface implemented, it is automatically
	 * selected as controller class.
	 *
	 * @param string $controllerClass
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException
	 */
	public function setControllerClass(string $controllerClass): ContentElementConfigurator {
		if (!in_array(ContentElementControllerInterface::class, class_implements($controllerClass)))
			throw new ContentElementConfigException("The given controller class $controllerClass is invalid, as it does not implement the controller interface!");
		if (!$this->backendListLabelRendererWasSet)
			if (class_exists($controllerClass) && in_array(BackendListLabelRendererInterface::class, class_implements($controllerClass)))
				$this->backendListLabelRenderer = $controllerClass;
		$this->controllerClass = $controllerClass;
		return $this;
	}
	
	/**
	 * Returns the icon of this element
	 * @return string
	 */
	public function getIcon(): string {
		return $this->icon;
	}
	
	/**
	 * Sets the path like EXT:extkey... to a icon for this element.
	 * If not given the ext_icon.gif in the root directory will be used.
	 *
	 * @param string $icon
	 *
	 * @return $this
	 */
	public function setIcon(string $icon): ContentElementConfigurator {
		$this->icon = $icon;
		return $this;
	}
	
	/**
	 * Returns true if the content element should be cached, false if not
	 * @return bool
	 */
	public function isUseCache(): bool {
		return $this->useCache;
	}
	
	/**
	 * If set to false this content element will not be cached.
	 *
	 * @param bool $useCache
	 *
	 * @return ContentElementConfigurator
	 */
	public function setUseCache(bool $useCache): ContentElementConfigurator {
		$this->useCache = $useCache;
		return $this;
	}
	
	/**
	 * Returns the TCA form that is used for this content element
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\Table\ContentElementForm
	 */
	public function getForm(): ContentElementForm {
		if (empty($this->form))
			$this->form = ContentElementForm::makeInstance($this->getSignature(), $this->table, $this->context, $this->defaultType);
		return $this->form;
	}
	
	/**
	 * Returns the currently configured model class for the content element
	 * @return string|null
	 */
	public function getModelClass(): string {
		return $this->modelClass;
	}
	
	/**
	 * Can be used to set the model that is used to represent this content element's data
	 * The given class should extend the AbstractContentElementModel
	 *
	 * @param string $modelClass
	 *
	 * @return ContentElementConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException
	 */
	public function setModelClass(string $modelClass): ContentElementConfigurator {
		if (!in_array(AbstractContentElementModel::class, class_parents($modelClass)))
			throw new ContentElementConfigException("The given model class: $modelClass has to extend the " . AbstractContentElementModel::class);
		$this->modelClass = $modelClass;
		return $this;
	}
	
	/**
	 * Returns true if this controller should render a backend preview, false if not
	 * @return bool
	 */
	public function renderBackendPreview(): bool {
		return $this->backendPreview;
	}
	
	/**
	 * If this is set to false, the content element will not render a backend preview
	 *
	 * @param bool $state
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator
	 */
	public function setBackendPreview(bool $state): ContentElementConfigurator {
		$this->backendPreview = $state;
		return $this;
	}
	
	/**
	 * Adds one ore more css classes to the list of global content element classes.
	 * The classes you set here will be distributed to every content element that is provided in the frontend.
	 *
	 * @param $classes
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function addCssClasses($classes): ContentElementConfigurator {
		if (is_string($classes)) $classes = Arrays::makeFromStringList($classes, " ");
		if (!is_array($classes)) throw new JsonApiException("Invalid css class list given!");
		$this->cssClasses = array_unique(Arrays::attach($this->cssClasses, $classes));
		return $this;
	}
	
	/**
	 * Removes one or multiple classes from the global content element registration
	 *
	 * @param $classes
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function removeCssClasses($classes): ContentElementConfigurator {
		if (is_string($classes)) $classes = Arrays::makeFromStringList($classes, " ");
		if (!is_array($classes)) throw new JsonApiException("Invalid css class list given!");
		$this->cssClasses = array_diff($this->cssClasses, $classes);
		return $this;
	}
	
	/**
	 * Returns the list of all registered css classes.
	 * This does not include globally registered css classes!
	 *
	 * @return array
	 */
	public function getCssClasses(): array {
		return $this->cssClasses;
	}
	
	/**
	 * Returns either the configured backend list label renderer class, a list of fields that should be rendered or
	 * null if there is nothing configured
	 * @return string|array|null
	 */
	public function getBackendListLabelRenderer() {
		return $this->backendListLabelRenderer;
	}
	
	/**
	 * Can be used to define the backend preview renderer class.
	 * The given class should implement the BackendListLabelRendererInterface, may be the same class as the plugin
	 * configuration and/or the plugin controller. You can also specify an array of column names that should
	 * be used as descriptions in your label. In that case the internal renderer will handle the rest.
	 *
	 * NOTE: If either your controller class implements the BackendListLabelRendererInterface
	 * it is automatically selected as backend preview renderer.
	 *
	 * @param string|array|null $backendListLabelRenderer
	 *
	 * @return ContentElementConfigurator
	 * @see \LaborDigital\Typo3BetterApi\BackendPreview\BackendListLabelRendererInterface
	 */
	public function setBackendListLabelRenderer($backendListLabelRenderer): ContentElementConfigurator {
		$this->backendListLabelRendererWasSet = TRUE;
		$this->backendListLabelRenderer = $backendListLabelRenderer;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getDataHandlerTableName(): string {
		return "tt_content";
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getDataHandlerFieldConstraints(): array {
		if ($this->isUsedForExistingElement()) return ["CType" => $this->getExistingElementCType()];
		return ["CType" => $this->signature];
	}
	
	/**
	 * Generates the controller class name for the given base name
	 *
	 * @param string $baseName
	 *
	 * @return string
	 */
	protected function makeModelClassName(string $baseName): string {
		return implode('\\', array_filter([
			ucfirst($this->context->getVendor()),
			Inflector::toCamelCase($this->context->getExtKey()),
			'Domain\\Model\\ContentElement',
			Inflector::toCamelCase($baseName) . "Model",
		]));
	}
}