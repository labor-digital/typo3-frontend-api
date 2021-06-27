<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.06.22 at 18:22
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement\Adapter;


use LaborDigital\T3ba\Core\Exception\NotImplementedException;
use TYPO3\CMS\Extbase\Mvc\View\AbstractView;
use TYPO3\CMS\Extbase\Mvc\View\NotFoundView;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class ViewAdapter extends AbstractView
{
    /**
     * @inheritDoc
     */
    public function render()
    {
        throw new NotImplementedException('This is an adapter not an actual view object!');
    }
    
    /**
     * Removes all currently set variables in the given view object
     *
     * @param   \TYPO3\CMS\Extbase\Mvc\View\AbstractView  $view
     */
    public static function clearVariables(AbstractView $view): void
    {
        $view->variables = [];
    }
    
    /**
     * Returns the list of all variables currently set to the given view object
     *
     * @param   \TYPO3\CMS\Extbase\Mvc\View\AbstractView  $view
     *
     * @return array
     */
    public static function getVariables(ViewInterface $view): array
    {
        if ($view instanceof AbstractView) {
            $vars = $view->variables;
        } else {
            $vars = [];
            
            $ref = new \ReflectionObject($view);
            if ($ref->hasProperty('variables')) {
                $propRef = $ref->getProperty('variables');
                $propRef->setAccessible(true);
                $vars = $propRef->getValue($view);
            }
        }
        
        
        if ($view instanceof NotFoundView) {
            unset($vars['errorMessage']);
        }
        
        unset($vars['settings']);
        
        return $vars;
    }
}