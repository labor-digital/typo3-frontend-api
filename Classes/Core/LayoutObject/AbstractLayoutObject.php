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
 * Last modified: 2021.06.21 at 20:22
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\LayoutObject;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3fa\Core\LayoutObject\Renderer\CeRenderer;
use LaborDigital\T3fa\Core\LayoutObject\Renderer\DirectoryMenuRenderer;
use LaborDigital\T3fa\Core\LayoutObject\Renderer\LanguageMenuRenderer;
use LaborDigital\T3fa\Core\LayoutObject\Renderer\PageMenuRenderer;
use LaborDigital\T3fa\Core\LayoutObject\Renderer\RootLineMenuRenderer;

abstract class AbstractLayoutObject implements LayoutObjectInterface
{
    use ContainerAwareTrait;
    
    /**
     * Renders a new root line / breadcrumb menu
     *
     * @param   array  $options   The options to build this menu with
     *                            - offsetStart int (0): The offset from the start of the root line
     *                            - offsetEnd int(0): The offset from the end of the root line.
     *                            - entryLevel int(0): Defines at which level in the rootLine the menu should start. Default
     *                            is “0” which gives us a menu of the very first pages on the site.
     *                            - excludeUidList array: A list of uids that should be excluded from the menu
     *                            - includeNotInMenu bool (FALSE): If set to true the menu will include pages
     *                            that are marked as "don't show in menu"
     *                            - additionalFields array: An optional list of additional database fields to fetch
     *                            and append to each menu item
     *                            - postProcessor string: A class that can be used to filter/alter the menu array
     *                            before it is passed to the frontend api. Has to implement the
     *                            PageMenuPostProcessorInterface
     *                            - fileFields array: A list of page fields that should be resolved as fal file references
     *                            - itemPostProcessor string: Quite similar to postProcessor, but is applied on a per-item
     *                            level while the menu tree is generated. The class has to implement PageMenuItemPostProcessorInterface.
     *
     * @return array
     * @see PageMenuPostProcessorInterface
     * @see PageMenuItemPostProcessorInterface
     */
    protected function makeRootLineMenu(array $options = []): array
    {
        return $this->getService(RootLineMenuRenderer::class)->render($options);
    }
    
    /**
     * Renders a new directory menu
     *
     * @param   int|string  $pid      The pid to use as directory root. It defines which pages we should render
     * @param   array       $options  The options to build this menu with
     *                                - entryLevel int(0): Defines at which level in the rootLine the menu should start.
     *                                Default is “0” which gives us a menu of the very first pages on the site.
     *                                - excludeUidList array: A list of uids that should be excluded from the menu
     *                                - includeNotInMenu bool (FALSE): If set to true the menu will include pages
     *                                that are marked as "don't show in menu"
     *                                - additionalFields array: An optional list of additional database fields to fetch
     *                                and append to each menu item
     *                                - levels int(1): The number of levels we should render the nested menus recursively.
     *                                - showSpacers bool(FALSE): If set to true the "spacers" inside a menu will also be
     *                                rendered to the resulting array with a type of "spacer" instead of "link"
     *                                - postProcessor string: A class that can be used to filter/alter the menu array
     *                                before it is passed to the frontend api. Has to implement the
     *                                PageMenuPostProcessorInterface
     *                                - fileFields array: A list of page fields that should be resolved as fal file references
     *                                - itemPostProcessor string: Quite similar to postProcessor, but is applied on a per-item level while
     *                                the menu tree is generated. The class has to implement PageMenuItemPostProcessorInterface.
     *
     * @return array
     * @see PageMenuPostProcessorInterface
     * @see PageMenuItemPostProcessorInterface
     */
    protected function makeDirectoryMenu($pid, array $options = []): array
    {
        $options['pid'] = $pid;
        
        return $this->getService(DirectoryMenuRenderer::class)->render($options);
    }
    
    /**
     * Renders a language switcher menu
     *
     * @param   array  $options   The options to build this menu with
     *                            - postProcessor string: A class that can be used to filter/alter the menu array
     *                            before it is passed to the frontend api. Has to implement the
     *                            PageMenuPostProcessorInterface
     *
     * @return array
     * @see PageMenuPostProcessorInterface
     */
    protected function makeLanguageMenu(array $options = []): array
    {
        return $this->getService(LanguageMenuRenderer::class)->render($options);
    }
    
    /**
     * Registers a new page menu for this site
     *
     * @param   array  $options   The options to build this menu with
     *                            - entryLevel int(0): Defines at which level in the rootLine the menu should start.
     *                            Default is “0” which gives us a menu of the very first pages on the site.
     *                            - excludeUidList array: A list of uids that should be excluded from the menu
     *                            - includeNotInMenu bool (FALSE): If set to true the menu will include pages
     *                            that are marked as "don't show in menu"
     *                            - additionalFields array: An optional list of additional database fields to fetch
     *                            and append to each menu item
     *                            - levels int(2): The number of levels we should render the nested menus recursively.
     *                            - showSpacers bool(FALSE): If set to true the "spacers" inside a menu will also be
     *                            rendered to the resulting array with a type of "spacer" instead of "link"
     *                            - postProcessor string: A class that can be used to filter/alter the menu array
     *                            before it is passed to the frontend api. Has to implement the
     *                            PageMenuPostProcessorInterface
     *                            - fileFields array: A list of page fields that should be resolved as fal file references
     *                            - itemPostProcessor string: Quite similar to postProcessor, but is applied on a per-item
     *                            level while the menu tree is generated. The class has to implement PageMenuItemPostProcessorInterface.
     *
     * @return array
     * @see PageMenuPostProcessorInterface
     * @see PageMenuItemPostProcessorInterface
     */
    protected function makePageMenu(array $options = []): array
    {
        return $this->getService(PageMenuRenderer::class)->render($options);
    }
    
    /**
     * Renders an element of the tt_content table as a resource array
     *
     * @param   int    $uid                The uid of the element in the tt_content table
     * @param   array  $options            Additional options to simulate a specific environment:
     *                                     {@link EnvironmentSimulator::runWithEnvironment()}
     *
     * @return array
     */
    protected function makeContentElementWithId(int $uid, array $options = []): array
    {
        return $this->getService(CeRenderer::class)->renderWithId($uid, $options);
    }
    
    /**
     * Renders an element based on the typo script configuration on a given path
     *
     * @param   string  $typoScriptObjectPath  The typo script object path to find the definition at
     * @param   array   $options               Additional options to simulate a specific environment:
     *                                         {@link EnvironmentSimulator::runWithEnvironment()}
     *
     * @return array
     */
    protected function makeContentElementWithTsPath(string $typoScriptObjectPath, array $options = []): array
    {
        return $this->getService(CeRenderer::class)->renderWithPath($typoScriptObjectPath, $options);
    }
}