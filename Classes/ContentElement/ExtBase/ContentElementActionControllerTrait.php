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
 * Last modified: 2019.08.09 at 15:19
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\ExtBase;


use LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewRendererContext;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException;
use LaborDigital\Typo3FrontendApi\ContentElement\ContentElementHandler;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel;
use LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Trait JsFeatureActionControllerTrait
 * @package LaborDigital\Typo3FrontendApi\ComponentContentElements\ExtBase
 *
 * @property ObjectManager                 $objectManager
 * @property Response                      $response
 * @property Request                       $request
 * @property ConfigurationManagerInterface $configurationManager
 */
trait ContentElementActionControllerTrait {
	
	/**
	 * Returns the instance of the resource data repository
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository
	 */
	protected function getResourceDataRepository(): ResourceDataRepository {
		$this->validateThatTraitIsCalledInActionController();
		return $this->objectManager->get(ResourceDataRepository::class);
	}
	
	/**
	 * This method is used to handle the action of an extBase plugin controller like a dedicated content element.
	 * To do so you have to supply two callable functions. One to render the frontend, one for the backend. This is
	 * analog to the creation of an element controller object. The backend handler is optional and may be left empty.
	 * The third parameter accepts the model class that should be used to represent the tt_content row as php object.
	 *
	 * @param callable      $frontendHandler The function to execute as frontend handler. Receives the content element
	 *                                       context as parameter. Behaves exactly like a content element controller's
	 *                                       handle() method.
	 * @param callable|null $backendHandler  The function to execute as backend handler. Receives the content element
	 *                                       context as parameter. Behaves exactly like a content element controller's
	 *                                       handleBackend() method.
	 * @param string|null   $modelClass      An optional class to be used to represent the tt_content row as object.
	 *                                       The given class should extend AbstractContentElementModel
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\ExtBase\ContentElementActionControllerResult|mixed
	 * @throws \LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException
	 * @see AbstractContentElementModel
	 * @see \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
	 */
	protected function renderAsContentElement(callable $frontendHandler, ?callable $backendHandler = NULL, ?string $modelClass = NULL) {
		
		// Check if we are in an action controller
		$this->validateThatTraitIsCalledInActionController();
		
		// Check if the model is correctly set
		if (!empty($modelClass) && !in_array(AbstractContentElementModel::class, class_parents($modelClass)))
			throw new ContentElementException("The given model class does not extend the required: " . AbstractContentElementModel::class);
		
		// Set our handlers in the pseudo controller class
		ExtBaseContentElementController::$frontendHandler = $frontendHandler;
		ExtBaseContentElementController::$backendHandler = $backendHandler;
		
		// Check if there is only a single action method -> No need for deeper nesting
		$actions = get_class_methods($this);
		$actions = array_filter($actions, function ($action) {
			if (in_array($action, ["initializeAction", "errorAction", "callBackendAction"])) return FALSE;
			return preg_match("~Action$~si", $action);
		});
		$isSingleAction = count($actions) === 1;
		$type = Inflector::toCamelCase($this->request->getControllerExtensionName()) . "/" . Inflector::toCamelCase($this->request->getControllerName());
		if (!$isSingleAction) $type .= "/" . Inflector::toCamelCase($this->request->getControllerActionName()) . "/" .
			Inflector::toCamelCase($this->request->getControllerName() . "-" . $this->request->getControllerActionName());
		
		// Build the configuration
		$config = [
			"controllerClass" => ExtBaseContentElementController::class,
			"modelClass"      => $modelClass,
			"type"            => $type,
			"extKey"          => $this->request->getControllerExtensionKey(),
			"vendor"          => $this->request->getControllerVendorName(),
		];
		
		// Read the element row
		$isFrontend = $this->objectManager->get(TypoContext::class)->getEnvAspect()->isFrontend();
		if ($isFrontend) $data = $this->configurationManager->getContentObject()->data;
		else if (!empty($this->data)) $data = $this->data;
		else if (!empty($this->context)) {
			$context = $this->context;
			if ($context instanceof BackendPreviewRendererContext) $data = $context->getRow();
		}
		if (empty($data)) throw new ContentElementException("Could not retrieve the data of the content element. Make sure to use the BackendPreviewRendererTrait or supply the raw database row as \$this->data");
		
		// Get the handler
		$handler = $this->objectManager->get(ContentElementHandler::class);
		$result = $handler->handleCustom($data, $isFrontend, $config, [
			"contentObjectRenderer" => $isFrontend ? $this->configurationManager->getContentObject() : NULL,
			"controller"            => $this,
			"args"                  => [],
			"context"               => ($isFrontend ? NULL : (!empty($this->context) && $this->context instanceof BackendPreviewRendererContext ? $this->context : NULL)),
		]);
		
		// Save the data in the response
		if (empty($result)) $result = " ";
		$this->response->setContent($result);
		
		// Done
		return $this->objectManager->get(ContentElementActionControllerResult::class);
	}
	
	/**
	 * Internal helper to check if all our required properties exist
	 *
	 * @throws \LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException
	 */
	protected function validateThatTraitIsCalledInActionController() {
		if (!$this instanceof ActionController)
			throw new ContentElementException("To use this trait you have to call it in an ActionController action!");
	}
}