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
    final public function handle(ContentElementControllerContext $context)
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
        
        // Use the first action if the registered action was not found.
        $selectedAction = $context->getData()->frontend_api_ce_action;
        if (! isset($actions[$selectedAction])) {
            reset($actions);
            $selectedAction = key($actions);
        }
        
        // Build the stack of possible method names
        $methodNames = [$selectedAction, $selectedAction . 'Action', 'handle' . ucfirst($selectedAction)];
        foreach ($methodNames as $methodName) {
            // Check if we have matching action method
            if (method_exists($this, $methodName)) {
                // Simulate a plugin action
                $typeParts      = explode('/', $context->getType());
                $controllerName = end($typeParts);
                // Update the type
                $context->setType($context->getType() . '/' . ucfirst($selectedAction) . "/" .
                                  Inflector::toCamelCase($controllerName . "-" . $selectedAction));
                
                // Call the controller method
                return $this->$methodName($context);
            }
        }
        
        // Done
        if (TypoContainer::getInstance()->get(TypoContext::class)->Env()->isDev()) {
            return '<p style="background-color: yellow; padding: 0.5em 1em;"><strong>Failed to render content element: '
                   . $context->getCType() . ' because the selected action: ' . $selectedAction
                   . ' method was not found! I tried: ' . implode(', ', $methodNames) . '</strong></p>';
        }
        
        return '';
    }
}