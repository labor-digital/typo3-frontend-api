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
 * Last modified: 2019.08.27 at 12:57
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration\Table;


use LaborDigital\Typo3BetterApi\BackendForms\Abstracts\AbstractForm;
use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaField;
use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaPalette;
use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTab;
use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable;
use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;

class ContentElementForm {
	
	/**
	 * Dynamic columns
	 * @var array
	 */
	protected $virtualColumns = [];
	
	/**
	 * The type signature
	 * @var string
	 */
	protected $signature;
	
	/**
	 * The tca table type we perform our actions on
	 * @var \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTableType
	 */
	protected $type;
	
	/**
	 * The context object we use in this table
	 * @var \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
	 */
	protected $context;
	
	/**
	 * Can be used to move any element inside the form to any other position.
	 *
	 * Position can be defined as "field", "container" or "0" (tabs) to move the element AFTER the defined element.
	 *
	 * You may also use the following modifiers:
	 *    - before:field positions the element in front of the element with "field" as id
	 *    - after:field positions the element after the element with "field" as id
	 *    - top:container positions the element as first element of a container/tab
	 *    - bottom:container positions the element as last element of a container/tab
	 *
	 * If top/bottom are used in combination with a field (and not a container element) it will be translated to before
	 * or after respectively.
	 *
	 * @param string $id
	 * @param string $position
	 *
	 * @return bool
	 * @throws \LaborDigital\Typo3BetterApi\BackendForms\BackendFormException
	 */
	public function moveElement(string $id, string $position): bool {
		return $this->type->moveElement($id, $position);
	}
	
	/**
	 * Removes an element with the given id from this container
	 *
	 * Note: This looks only in the current container, not in the whole form
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function removeElement(string $id): bool {
		return $this->type->removeElement($id);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getField(string $id): TcaField {
		return $this->type->getField($id);
	}
	
	/**
	 * You may use this method if you want to resync the configuration of a given field
	 * with the default type. Note: The initially set columnOverrides will be applied again!
	 *
	 * @param string $id
	 *
	 * @return \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaField
	 */
	public function reloadFieldConfig(string $id): TcaField {
		return $this->type->reloadFieldConfig($id);
	}
	
	/**
	 * Returns the list of all registered fields that are currently inside the layout
	 *
	 * @return \LaborDigital\Typo3BetterApi\BackendForms\Abstracts\AbstractFormField[]
	 */
	public function getFields(): array {
		return $this->type->getFields();
	}
	
	/**
	 * Similar to getFields() but only returns the keys of the fields instead of the whole object
	 *
	 * @return array
	 */
	public function getFieldKeys(): array {
		return $this->type->getFieldKeys();
	}
	
	/**
	 * Returns true if a field with the given id is registered in this form
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function hasField(string $id): bool {
		return $this->type->hasField($id);
	}
	
	/**
	 * Returns the instance of a certain tab.
	 * Note: If the tab not exists, a new one will be created at the end of the form
	 *
	 * @param string $id
	 *
	 * @return \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTab
	 */
	public function getTab(string $id): TcaTab {
		return $this->type->getTab($id);
	}
	
	/**
	 * Returns true if a given tab exists, false if not
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function hasTab(string $id): bool {
		return $this->type->hasTab($id);
	}
	
	/**
	 * Returns a single palette instance
	 * Note: If the palette not exists, a new one will be created at the end of the form
	 *
	 * @param string $id
	 *
	 * @return \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaPalette
	 */
	public function getPalette(string $id): TcaPalette {
		return $this->type->getPalette($id);
	}
	
	/**
	 * Returns true if the layout has a palette with that id already registered
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function hasPalette(string $id): bool {
		return $this->type->hasPalette($id);
	}
	
	/**
	 * Returns the list of all palettes that are used inside of this form
	 *
	 * @return array
	 */
	public function getPalettes(): array {
		return $this->type->getPalettes();
	}
	
	/**
	 * Adds a new line break to palettes
	 *
	 * @param string $position The position where to add the tab. See moveElement() for details
	 *
	 * @return string
	 */
	public function addLineBreak(string $position = ""): string {
		return $this->type->addLineBreak($position);
	}
	
	/**
	 * Can be used to set raw config values, that are not implemented in this facade.
	 * Set either key => value pairs, or an Array of key => value pairs
	 *
	 * @param array|string|int $key   Either a key to set the given $value for, or an array of $key => $value pairs
	 * @param null             $value The value to set for the given $key (if $key is not an array)
	 *
	 * @return $this
	 */
	public function setRaw($key, $value = NULL) {
		$this->type->setRaw($key, $value);
		return $this;
	}
	
	/**
	 * Returns the raw configuration array for this object
	 * @return array
	 */
	public function getRaw(): array {
		return $this->type->getRaw();
	}
	
	/**
	 * Returns the TCA table configuration of the tt_content table
	 * @return \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable
	 * @throws \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException
	 */
	public function getTable(): TcaTable {
		$parent = $this->type->getParent();
		if (!$parent instanceof TcaTable) $parent = $parent->getParent();
		if (!$parent instanceof TcaTable) throw new ContentElementConfigException("Could not find the reference to the content element's tca table!");
		return $parent;
	}
	
	/**
	 * Internal helper to update the column names of the virtual columns.
	 * Don't use this on your own, as you may break stuff!
	 */
	public function __renameVirtualColumns() {
		if (empty($this->virtualColumns)) return;
		
		// Loop through all the virtual columns and rename them to a namespaced name...
		$virtualColumnsScoped = [];
		foreach ($this->virtualColumns as $column) {
			$columnScoped = "vCol_" . $this->signature . "_" . $column;
			$virtualColumnsScoped[$column] = $columnScoped;
			
			// Rename the element inside the tree
			$field = $this->type->getField($column);
			FormElementAdapter::updateElementId($field, $columnScoped);
			
			// Mark this column as virtual
			$fieldRaw = $field->getRaw();
			$fieldRaw["id"] = $column;
			$fieldRaw["frontendApiVirtualColumn"] = TRUE;
			$field->setRaw($fieldRaw);
		}
		$this->virtualColumns = $virtualColumnsScoped;
		
		// Process the display conditions to handle the virtual columns correctly
		foreach ($this->getFields() as $field) {
			$displayCond = $field->getDisplayCondition();
			if (empty($displayCond)) continue;
			if (stripos($displayCond, "field:") !== 0) continue;
			$cond = explode(":", $displayCond);
			if (!isset($this->virtualColumns[$cond[1]])) continue;
			$cond[1] = $this->virtualColumns[$cond[1]];
			$field->setDisplayCondition(implode(":", $cond));
		}
	}
	
	/**
	 * Returns the list of virtual columns that are configured using this form
	 * @return array
	 */
	public function __getVirtualColumns(): array {
		return $this->virtualColumns;
	}
	
	/**
	 * Factory method to create a new instance of a content element form.
	 *
	 * @param string                                                      $signature
	 * @param \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable $table
	 * @param \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext     $context
	 *
	 * @param array                                                       $defaultType
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\Table\ContentElementForm
	 */
	public static function makeInstance(string $signature, TcaTable $table, ExtConfigContext $context, array $defaultType): ContentElementForm {
		// Check if we got the type
		if (!$table->hasType($signature)) {
			$raw = $table->getRaw();
			$raw["types"][$signature] = $defaultType;
			$table->setRaw($raw);
		}
		
		// Create a new instance
		$self = TypoContainer::getInstance()->get(static::class);
		$self->type = $table->getType($signature);
		$self->context = $context;
		$self->signature = $signature;
		
		// Set new field resolver
		$self->type->__setFieldTcaResolver(function (string $fieldId) use ($table, $self) {
			// Check if the parent table already has this column -> map me to that
			if (Arrays::hasPath($GLOBALS, ["TCA", "tt_content", "columns", $fieldId]))
				return $table->getField($fieldId)->getRaw();
			$self->virtualColumns[$fieldId] = $fieldId;
			return array_merge(AbstractForm::DEFAULT_FIELD_CONFIG, ["label" => Inflector::toHuman($fieldId)]);
		});
		
		// Done
		return $self;
	}
}