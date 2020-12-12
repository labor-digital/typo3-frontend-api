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
 * Last modified: 2020.05.28 at 20:23
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ContentElement\Controller;


use LaborDigital\Typo3BetterApi\BackendForms\Abstracts\AbstractFormField;
use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;

/**
 * Trait SwitchableContentElementActionTrait
 *
 * Allows you to implement simple, switchable controller actions in your content elements
 *
 * @package LaborDigital\Typo3FrontendApi\ContentElement\Controller
 */
trait SwitchableContentElementActionTrait
{
    /**
     * Registers the possible actions in this content element.
     * Has to be called in your content elements' configureElement() method to work properly
     *
     * @param   array                       $actions       The list of possible actions and their readable labels.
     *                                                     Registered as $key => $label pairs, your $key also
     *                                                     is the name of your controller action.
     *                                                     Example: ['foo' => 'Foo Action'] will either call one of the
     *                                                     following methods (in that order, using the first matching):
     *                                                     foo(), fooAction() or handleFoo().
     *                                                     The action gets the context injected as with a default
     *                                                     handle() method.
     * @param   ContentElementConfigurator  $configurator  The configurator instance to register your field config on
     *
     * @return \LaborDigital\Typo3BetterApi\BackendForms\Abstracts\AbstractFormField
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException
     */
    protected static function registerSwitchableActions(
        array $actions,
        ContentElementConfigurator $configurator
    ): AbstractFormField {
        // Validate the actions
        if (empty($actions)) {
            throw new ContentElementConfigException('The action definition must not be empty!');
        }

        // Register the field configuration
        return $configurator
            ->getForm()->getField('frontend_api_ce_action')
            ->applyPreset()
            ->select($actions)
            ->moveTo('after:CType')
            ->addConfig(['switchableActions' => $actions]);
    }

    /**
     * Handles the content element request using one of the registered action types
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     *
     * @return mixed
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException
     */
    public function handle(ContentElementControllerContext $context)
    {
        // Call the action
        $selectedAction = $this->getSelectedAction($context);

        return $this->callControllerAction($selectedAction, $context, [$context]);
    }

    /**
     * Acts as a proxy to provide an error handler for each of the registered actions.
     *
     * If your method is called "list", the error handler can be either listError(), listErrorAction() or handleListError(),
     * where the first matching method will be used.
     *
     * Similar to the handleError() method the result of the error handler must be either a string or null.
     *
     * @param   \Throwable                                                                                $error
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     * @param   bool                                                                                      $frontend
     *
     * @return string|null
     */
    public function handleError(\Throwable $error, ContentElementControllerContext $context, bool $frontend): ?string
    {
        $method = $this->getSelectedAction($context);

        return $this->callControllerAction(
            Inflector::toCamelBack($method . '-Error'), $context, func_get_args());
    }

    /**
     * Internal helper to resolve the list of registered actions as an array
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     *
     * @return array
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigException
     * @internal
     */
    protected function getRegisteredActions(ContentElementControllerContext $context): array
    {
        // Try to retrieve the switchable option definition
        $actions = Arrays::getPath($GLOBALS,
            [
                'TCA',
                'tt_content',
                'types',
                $context->getCType(),
                'columnsOverrides',
                'frontend_api_ce_action',
                'config',
                'switchableActions',
            ]);

        // Validate the actions
        if (! is_array($actions) || empty($actions)) {
            throw new ContentElementConfigException('The switchable content element controller actions have to be an array! Did you register the actions using registerSwitchableActions() in your configureElement() method?');
        }

        return $actions;
    }

    /**
     * Returns either the selected or the first possible action in the list of registered actions
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     *
     * @return string
     */
    protected function getSelectedAction(ContentElementControllerContext $context): string
    {
        $actions        = $this->getRegisteredActions($context);
        $selectedAction = $context->getModel()->frontend_api_ce_action;
        if (! isset($actions[$selectedAction])) {
            reset($actions);
            $selectedAction = key($actions);
        }

        return $selectedAction;
    }

    /**
     * Returns the (possibly translated) label for the currently selected action as a string
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     *
     * @return string
     */
    protected function getSelectedActionLabel(ContentElementControllerContext $context): string
    {
        $actions = $this->getRegisteredActions($context);
        $action  = $this->getSelectedAction($context);

        foreach ($actions as $pair) {
            if (! is_array($pair)) {
                continue;
            }
            if ($action === (string)$pair[1]) {
                return $this->Translation()->translateMaybe((string)$pair[0]);
            }
        }

        return 'Unknown';
    }

    /**
     * Helper to execute a controller action. Just Provide it with the correct controller action name and the context.
     * The result will be either the result of the executed action method
     *
     * @param   string                           $selectedAction
     * @param   ContentElementControllerContext  $context
     * @param   array                            $arguments           The arguments to pass to the action method
     * @param   bool                             $allowMissingAction  If true, it is considered OK if no action could be resolved.
     *
     * @return mixed
     */
    protected function callControllerAction(
        string $selectedAction,
        ContentElementControllerContext $context,
        array $arguments,
        bool $allowMissingAction = false
    ) {
        // Build the stack of possible method names
        $methodNames = [$selectedAction, $selectedAction . 'Action', 'handle' . ucfirst($selectedAction)];
        foreach ($methodNames as $methodName) {
            // Check if we have matching action method
            if (method_exists($this, $methodName)) {
                // Simulate a plugin action
                $typeParts      = explode('/', $context->getType());
                $controllerName = end($typeParts);
                // Update the type
                $context->setType($context->getType() . '/' . ucfirst($selectedAction) . '/' .
                                  Inflector::toCamelCase($controllerName . '-' . $selectedAction));

                // Call the controller method
                return $this->$methodName(...$arguments);
            }
        }

        if ($allowMissingAction) {
            return null;
        }

        if (TypoContainer::getInstance()->get(TypoContext::class)->Env()->isDev()) {
            return '<p style="background-color: yellow; padding: 0.5em 1em;"><strong>Failed to render content element: '
                   . $context->getCType() . ' because the selected action: ' . $selectedAction
                   . ' method was not found! I tried: ' . implode(', ', $this->getRegisteredActions($context)) . '</strong></p>';
        }

        return '';
    }


}
