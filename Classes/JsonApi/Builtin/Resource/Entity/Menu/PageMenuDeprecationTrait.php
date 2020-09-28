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
 * Last modified: 2020.09.25 at 12:10
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\Page\PageService;
use LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuHtmlFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuPreProcessorEvent;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait PageMenuDeprecationTrait
 *
 * @package    LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu
 * @deprecated this trait and all linked functionality will be removed in v10
 */
trait PageMenuDeprecationTrait
{
    use FrontendApiContextAwareTrait;

    /**
     * Factory method to create a new instance of myself
     *
     * @param   string  $key
     * @param   array   $options
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\PageMenu
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(string $key, array $options): PageMenu
    {
        return TypoContainer::getInstance()->get(static::class, ['args' => [$key, $options['type'], $options['options']]]);
    }

    protected function renderLegacyPageMenu(): array
    {
        // Build the menu
        $break          = $this->getUniqueBreak();
        $menuDefinition = $this->getRecursiveMenuDefinition($break);
        $menu           = $this->processMenuDefinition($menuDefinition);
        $menuParsed     = $this->processRecursiveMenu($menu, $break);

        return $this->runPostProcessing($menuParsed);
    }

    protected function renderLegacyRootLineMenu(): array
    {
        // Build the menu html
        $break = $this->getUniqueBreak();
        $menu  = $this->processMenuDefinition([
            'entryLevel'       => $this->options['entryLevel'],
            'excludeUidList'   => implode(',', $this->options['excludeUidList']),
            'includeNotInMenu' => $this->options['includeNotInMenu'],
            'special'          => 'rootline',
            'special.'         => [
                'range' => $this->options['offsetStart'] . '|' . (empty($this->options['offsetEnd']) ? '999' : (-abs($this->options['offsetEnd']) - 1)),
            ],
            '1'                => 'TMENU',
            '1.'               => [
                'expAll' => true,
                'NO.'    => [
                    'wrapItemAndSub' => "|$break",
                    'before.'        => $this->getEntryMetaBefore(),
                ],
                'CUR.'   => [
                    'wrapItemAndSub' => "|$break",
                    'before.'        => $this->getEntryMetaBefore(),
                ],
            ],
        ]);


        // Parse the menu html
        $menuParts  = explode($break, $menu);
        $menuParsed = [];
        foreach ($menuParts as $part) {
            if (empty($part)) {
                continue;
            }
            $menuParsed[] = $this->processSingleLegacyMenuItem($part, $this->options['additionalFields']);
        }

        return $this->runPostProcessing($menuParsed);
    }

    protected function renderLegacyDirectoryMenu(): array
    {
        // Prepare the definition
        $break                               = $this->getUniqueBreak();
        $menuDefinition                      = $this->getRecursiveMenuDefinition($break);
        $menuDefinition['special']           = 'directory';
        $menuDefinition['special.']['value'] = $this->options['pid'];

        // Build the menu
        $menu       = $this->processMenuDefinition($menuDefinition);
        $menuParsed = $this->processRecursiveMenu($menu, $break);

        return $this->runPostProcessing($menuParsed);
    }

    protected function getEntryMetaBefore(): array
    {
        return [
            'stdWrap.' => [
                'wrap'     => '|',
                'cObject'  => 'TEXT',
                'cObject.' => [
                    'field' => 'doktype',
                    'wrap'  => 'TYPE:|:TYPE',
                ],

                'append'  => 'TEXT',
                'append.' => [
                    'field' => 'uid',
                    'wrap'  => 'ID:|:ID',
                ],
            ],
        ];
    }

    /**
     * Helper to build the typoScript definition for a hierarchical page menu
     *
     * @param   array   $options  The given list of options
     * @param   string  $break    The break marker which separates the field from the children
     *
     * @return array
     */
    protected function getRecursiveMenuDefinition(string $break): array
    {
        // Prepare the menu definition
        $menuDefinition = [
            'entryLevel'       => $this->options['entryLevel'],
            'excludeUidList'   => implode(',', $this->options['excludeUidList']),
            'includeNotInMenu' => $this->options['includeNotInMenu'],
        ];

        // Build a single menu level
        $levelDefinition = [
            'expAll' => true,
            'NO.'    => [
                'wrapItemAndSub' => '|',
                'before.'        => $this->getEntryMetaBefore(),
            ],
        ];
        if ($this->options['showSpacers']) {
            $levelDefinition['SPC']  = true;
            $levelDefinition['SPC.'] = [
                'doNotLinkIt'   => true,
                'doNotShowLink' => true,
                'stdWrap.'      => [
                    'wrap' => '<a href="[SPACER]">|</a>',
                ],
                'before.'       => $this->getEntryMetaBefore(),
            ];
        }
        for ($i = 1; $i <= $this->options['levels']; $i++) {
            $menuDefinition[$i]                        = 'TMENU';
            $levelDefinition['NO.']['wrapItemAndSub']  = "${break}[LEVEL_$i:|:LEVEL_$i]";
            $levelDefinition['SPC.']['wrapItemAndSub'] = $levelDefinition['NO.']['wrapItemAndSub'];
            $menuDefinition[$i . '.']                  = $levelDefinition;
        }

        return $menuDefinition;
    }

    /**
     * Returns a random break marker
     *
     * @return string
     */
    protected function getUniqueBreak(): string
    {
        return '[BR:' . md5(microtime(true) . random_bytes(20)) . ':BR]';
    }

    /**
     * Internal helper to travers the menu's html tree recursively and convert it into an array
     *
     * @param   string  $html     The menu's html to traverse
     * @param   string  $break    The break marker which separates the field from the children
     * @param   array   $options  The list of options for the processed menu
     *
     * @return array
     */
    protected function processRecursiveMenu(string $html, string $break): array
    {
        // Parse the menu recursively
        $recursionWalker = function (string $html, int $level, int $maxLevel, \Closure $recursionWalker) use ($break): array {
            $result = [];
            preg_match_all("~\[LEVEL_$level:(.*?):LEVEL_$level]~si", $html, $parts);
            foreach ($parts[1] as $part) {
                // Extract the element itself
                $breakPos = stripos($part, $break);
                $children = null;
                if ($breakPos === false) {
                    // There are no children
                    $element = $part;
                } else {
                    $element = substr($part, 0, $breakPos);
                    // Don't go further if we reached our depth limit
                    if ($level + 1 <= $maxLevel) {
                        $children = $recursionWalker(substr($part, $breakPos), $level + 1, $maxLevel, $recursionWalker);
                    }
                }
                // Parse the element
                $el             = $this->processSingleLegacyMenuItem($element, $this->options['additionalFields']);
                $el['children'] = $children;
                $result[]       = $el;
            }

            return $result;
        };

        return $recursionWalker($html, 1, $this->options['levels'], $recursionWalker);
    }

    /**
     * Internal helper to parse a single html a tag inside a menu
     *
     * @param   string  $html              The menu html we should parse. We expect a string like: ID:$ID:ID<a
     *                                     href="$SLUG">$TITLE</a>
     * @param   array   $additionalFields  A list of additional fields we should fetch from the database
     *
     * @return array
     */
    protected function processSingleLegacyMenuItem(string $html, array $additionalFields = []): array
    {
        preg_match_all("~TYPE:(\d+):TYPEID:(\d+):ID.*?href=\"([^\"]*?)\".*?>(.*?)</a>~si", $html, $m);

        // Build the element
        $dokType = (int)$m[1][0];
        $el      = [
            'type'  => static::TYPE_LINK_PAGE,
            'id'    => (int)$m[2][0],
            'href'  => $m[3][0],
            'title' => $m[4][0],
        ];

        // Handle spacer
        if ($dokType === 199) {
            $el['type'] = static::TYPE_LINK_SPACER;
            $el['href'] = null;
        }

        // Handle external link
        if ($dokType === 3) {
            $isExternal = ! GeneralUtility::isOnCurrentHost($el['href']);
            if ($isExternal) {
                $el['type'] = static::TYPE_LINK_EXTERNAL;
            } else {
                $el['type'] = static::TYPE_LINK_INTERNAL;
            }
        }

        // Fetch additional fields if required
        if (! empty($additionalFields)) {
            $pageInfo = $this->FrontendApiContext()->getSingletonOf(PageService::class)->getPageInfo($el['id']);
            foreach ($additionalFields as $field) {
                $propertyName = Inflector::toCamelBack($field);
                if (isset($pageInfo[$field])) {
                    $el['fields'][$propertyName] = $pageInfo[$field];
                } else {
                    $el['fields'][$propertyName] = null;
                }
            }
        }

        // Done
        return $el;
    }

    /**
     * Internal helper to allow event based filtering of the generated menu data
     *
     * @param   array  $menuDefinition  The prepared typo script definition of the menu
     *
     * @return string
     */
    protected function processMenuDefinition(array $menuDefinition): string
    {
        // Allow filtering
        $this->FrontendApiContext()->EventBus()->dispatch(($e = new SiteMenuPreProcessorEvent(
            $menuDefinition, $this->type, $this->key, $this->options)));
        $menuDefinition = $e->getDefinition();
        $render         = $e->isRender();

        // Render the menu
        $menu = '';
        if ($render) {
            $menu = $this->FrontendApiContext()
                         ->getSingletonOf(TypoScriptService::class)->renderContentObject('HMENU', $menuDefinition);
        }

        // Allow filtering
        $this->FrontendApiContext()->EventBus()->dispatch(($e = new SiteMenuHtmlFilterEvent(
            $menuDefinition, $this->type, $menu)));
        $menu = $e->getMenu();

        // Done
        return $menu;
    }
}
