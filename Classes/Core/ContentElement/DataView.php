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
 * Last modified: 2021.06.07 at 16:14
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement;


use TYPO3\CMS\Extbase\Mvc\View\AbstractView;

class DataView extends AbstractView
{
    /**
     * @inheritDoc
     */
    public function render()
    {
        return '';
    }
    
    /**
     * Removes all previously set variables
     */
    public function clear(): void
    {
        $this->variables = [];
    }
    
    /**
     * Returns all registered variables
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->variables;
    }
}