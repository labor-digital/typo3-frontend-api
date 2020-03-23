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
 * Last modified: 2019.08.27 at 23:16
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration\Table;


use LaborDigital\Typo3BetterApi\BackendForms\Abstracts\AbstractFormContainer;
use LaborDigital\Typo3BetterApi\BackendForms\Abstracts\AbstractFormElement;

class FormElementAdapter extends AbstractFormContainer {
	
	/**
	 * This adapter is used to change the id of a form element.
	 * This is not an intended feature by the form configuration logic, but it makes sense
	 * for the virtual elements to be renamed afterwards. Otherwise you would have to type the long vCol id over and
	 * over again, which isn't really intuitive...
	 *
	 * @param \LaborDigital\Typo3BetterApi\BackendForms\Abstracts\AbstractFormElement $element
	 * @param string                                                                  $id
	 */
	public static function updateElementId(AbstractFormElement $element, string $id) {
		// Change the element id
		$oldId = $element->id;
		$element->id = $id;
		
		// Change the element id in the parent element list
		$parent = $element->getParent();
		if ($parent instanceof AbstractFormContainer && isset($parent->elements[$oldId])) {
			$elementsFiltered = [];
			foreach ($parent->elements as $elId => $el) {
				if ($elId === $oldId) {
					$elementsFiltered[$id] = $el;
					continue;
				}
				$elementsFiltered[$elId] = $el;
			}
			$parent->elements = $elementsFiltered;
		}
	}
}