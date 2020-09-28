<?php
/*
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
 * Last modified: 2020.09.25 at 14:34
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


interface PageMenuItemPostProcessorInterface
{

    /**
     * Used to filter a menu item after it was generated. This method is called once for every item in the menu
     *
     * @param   string  $key       The name/key that was given when the menu was registered
     * @param   array   $item      The prepared menu item that should be processed
     * @param   array   $data      The raw record data for the menu item
     * @param   array   $options   The options that were given when the menu was registered
     * @param   string  $menuType  The type of the menu that was generated. One of PageMenu::TYPE_MENU_...
     *
     * @return array Must return the modified $menu array to be passed to the frontend
     * @see PageMenuPostProcessorInterface to filter a complete menu instead of the single items
     */
    public function processItem(string $key, array $item, array $data, array $options, string $menuType): array;

}
