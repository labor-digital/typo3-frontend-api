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
 * Last modified: 2019.09.23 at 06:16
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use Closure;
use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuHtmlFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\SiteMenuPreProcessorEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageMenu implements SelfTransformingInterface
{
    use CommonServiceLocatorTrait;

    protected const ENTRY_META_BEFORE
        = [
            "stdWrap." => [
                "wrap"     => "|",
                "cObject"  => "TEXT",
                "cObject." => [
                    "field" => "doktype",
                    "wrap"  => "TYPE:|:TYPE",
                ],

                "append"  => "TEXT",
                "append." => [
                    "field" => "uid",
                    "wrap"  => "ID:|:ID",
                ],
            ],
        ];

    public const TYPE_MENU_ROOT_LINE = "rootLineMenu";
    public const TYPE_MENU_PAGE      = "pageMenu";
    public const TYPE_MENU_DIRECTORY = "dirMenu";

    /**
     * Is a link that is probably handled by the router of the frontend framework
     */
    public const TYPE_LINK_PAGE = "linkPage";

    /**
     * Is a link that is on the current site, but should be handled as normal html link.
     */
    public const TYPE_LINK_INTERNAL = "linkInternal";

    /**
     * This link is not on the current host and should probably open in a new tab.
     */
    public const TYPE_LINK_EXTERNAL = "linkExternal";

    /**
     * This is a pseudo element and not a real link.
     * It is only active if the "showSpacer" option is enabled.
     */
    public const TYPE_LINK_SPACER = "spacer";

    /**
     * The options that are defining how the menu should look like
     *
     * @var array
     */
    protected $options;

    /**
     * The key that was given for this menu/common element
     *
     * @var string
     */
    protected $key;

    /**
     * PageMenu constructor.
     *
     * @param   string  $key      The key that was given for this menu/common element
     * @param   array   $options  The options that are defining how the menu should look like
     */
    public function __construct(string $key, array $options)
    {
        $this->key     = $key;
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        switch ($this->options["type"]) {
            case static::TYPE_MENU_PAGE:
                return $this->renderPageMenu($this->options["options"]);
            case static::TYPE_MENU_DIRECTORY:
                return $this->renderDirectoryMenu($this->options["options"]);
            case static::TYPE_MENU_ROOT_LINE:
                return $this->renderRootLineMenu($this->options["options"]);
            default:
                throw new JsonApiException("The menu is not configured correctly! There is no menu type: " . $this->options["type"]);
        }
    }

    /**
     * Factory method to create a new instance of myself
     *
     * @param   string  $key
     * @param   array   $options
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageMenu
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(string $key, array $options): PageMenu
    {
        return TypoContainer::getInstance()->get(static::class, ["args" => [$key, $options]]);
    }

    /**
     * Renders the array representation of a root line menu for the current page
     *
     * @param   array  $options  An array of options
     *                           - offsetStart int (0): The offset from the start of the root line
     *                           - offsetEnd int(0): The offset from the end of the root line.
     *                           - entryLevel int(0): Defines at which level in the rootLine the menu should start. Default
     *                           is “0” which gives us a menu of the very first pages on the site.
     *                           - excludeUidList array: A list of uid's that should be excluded from the menu
     *                           - includeNotInMenu bool (FALSE): If set to true the menu will include pages
     *                           that are marked as "don't show in menu"
     *                           - additionalFields array: An optional list of additional database fields to fetch
     *                           and append to each menu item
     *
     * @return array
     */
    protected function renderRootLineMenu(array $options): array
    {
        // Build the menu html
        $break = $this->getUniqueBreak();
        $menu  = $this->processMenuDefinition(static::TYPE_MENU_ROOT_LINE, [
            "entryLevel"       => $options["entryLevel"],
            "excludeUidList"   => implode(",", $options["excludeUidList"]),
            "includeNotInMenu" => $options["includeNotInMenu"],
            "special"          => "rootline",
            "special."         => [
                "range" => $options["offsetStart"] . "|" . (empty($options["offsetEnd"]) ? "999" : (-abs($options["offsetEnd"]) - 1)),
            ],
            "1"                => "TMENU",
            "1."               => [
                "expAll" => true,
                "NO."    => [
                    "wrapItemAndSub" => "|$break",
                    "before."        => static::ENTRY_META_BEFORE,
                ],
                "CUR."   => [
                    "wrapItemAndSub" => "|$break",
                    "before."        => static::ENTRY_META_BEFORE,
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
            $menuParsed[] = $this->processSingleMenuItem($part, $options["additionalFields"]);
        }

        // Done
        return $this->runPostProcessing(static::TYPE_MENU_ROOT_LINE, $menuParsed, $options);
    }

    /**
     * Renders a multi-hierarchical page menu aka a site menu as an array.
     *
     * @param   array  $options  An array of options
     *                           - entryLevel int(0): Defines at which level in the rootLine the menu should start. Default
     *                           is “0” which gives us a menu of the very first pages on the site.
     *                           - excludeUidList array: A list of uid's that should be excluded from the menu
     *                           - includeNotInMenu bool (FALSE): If set to true the menu will include pages
     *                           that are marked as "don't show in menu"
     *                           - additionalFields array: An optional list of additional database fields to fetch
     *                           and append to each menu item
     *                           - levels int(2): The number of levels we should render the nested menus recursively.
     *                           - showSpacers bool(FALSE): If set to true the "spacers" inside a menu will also be
     *                           rendered to the resulting array with a type of "spacer" instead of "link"
     *
     * @return array
     */
    protected function renderPageMenu(array $options): array
    {
        // Build the menu
        $break          = $this->getUniqueBreak();
        $menuDefinition = $this->getRecursiveMenuDefinition($options, $break);
        $menu           = $this->processMenuDefinition(static::TYPE_MENU_PAGE, $menuDefinition);
        $menuParsed     = $this->processRecursiveMenu($menu, $break, $options);

        return $this->runPostProcessing(static::TYPE_MENU_PAGE, $menuParsed, $options);
    }

    /**
     * Renders a directory menu which requires a defined "pid" as starting point as array.
     * This kind of menu may be multi-hierarchical but is configured by default to show only a single level of elements.
     *
     * @param   array  $options  An array of options
     *                           - pid int: Required pid to define the starting point of this menu
     *                           - entryLevel int(0): Defines at which level in the rootLine the menu should start. Default
     *                           is “0” which gives us a menu of the very first pages on the site.
     *                           - excludeUidList array: A list of uid's that should be excluded from the menu
     *                           - includeNotInMenu bool (FALSE): If set to true the menu will include pages
     *                           that are marked as "don't show in menu"
     *                           - additionalFields array: An optional list of additional database fields to fetch
     *                           and append to each menu item
     *                           - levels int(1): The number of levels we should render the nested menus recursively.
     *                           - showSpacers bool(FALSE): If set to true the "spacers" inside a menu will also be
     *                           rendered to the resulting array with a type of "spacer" instead of "link"
     *
     * @return array
     */
    protected function renderDirectoryMenu(array $options): array
    {
        // Prepare the definition
        $break                               = $this->getUniqueBreak();
        $menuDefinition                      = $this->getRecursiveMenuDefinition($options, $break);
        $menuDefinition["special"]           = "directory";
        $menuDefinition["special."]["value"] = $options["pid"];

        // Build the menu
        $menu       = $this->processMenuDefinition(static::TYPE_MENU_DIRECTORY, $menuDefinition);
        $menuParsed = $this->processRecursiveMenu($menu, $break, $options);

        return $this->runPostProcessing(static::TYPE_MENU_DIRECTORY, $menuParsed, $options);
    }

    /**
     * Helper to build the typoScript definition for a hierarchical page menu
     *
     * @param   array   $options  The given list of options
     * @param   string  $break    The break marker which separates the field from the children
     *
     * @return array
     */
    protected function getRecursiveMenuDefinition(array $options, string $break): array
    {
        // Prepare the menu definition
        $menuDefinition = [
            "entryLevel"       => $options["entryLevel"],
            "excludeUidList"   => implode(",", $options["excludeUidList"]),
            "includeNotInMenu" => $options["includeNotInMenu"],
        ];

        // Build a single menu level
        $levelDefinition = [
            "expAll" => true,
            "NO."    => [
                "wrapItemAndSub" => "|",
                "before."        => static::ENTRY_META_BEFORE,
            ],
        ];
        if ($options["showSpacers"]) {
            $levelDefinition["SPC"]  = true;
            $levelDefinition["SPC."] = [
                "doNotLinkIt"   => true,
                "doNotShowLink" => true,
                "stdWrap."      => [
                    "wrap" => "<a href=\"[SPACER]\">|</a>",
                ],
                "before."       => static::ENTRY_META_BEFORE,
            ];
        }
        for ($i = 1; $i <= $options["levels"]; $i++) {
            $menuDefinition[$i]                        = "TMENU";
            $levelDefinition["NO."]["wrapItemAndSub"]  = "${break}[LEVEL_$i:|:LEVEL_$i]";
            $levelDefinition["SPC."]["wrapItemAndSub"] = $levelDefinition["NO."]["wrapItemAndSub"];
            $menuDefinition[$i . "."]                  = $levelDefinition;
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
        return "[BR:" . md5(microtime(true) . random_bytes(20)) . ":BR]";
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
    protected function processRecursiveMenu(string $html, string $break, array $options): array
    {
        // Parse the menu recursively
        $recursionWalker = function (string $html, int $level, int $maxLevel, Closure $recursionWalker) use ($break, $options): array {
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
                $el             = $this->processSingleMenuItem($element, $options["additionalFields"]);
                $el["children"] = $children;
                $result[]       = $el;
            }

            return $result;
        };

        return $recursionWalker($html, 1, $options["levels"], $recursionWalker);
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
    protected function processSingleMenuItem(string $html, array $additionalFields = []): array
    {
        preg_match_all("~TYPE:(\d+):TYPEID:(\d+):ID.*?href=\"([^\"]*?)\".*?>(.*?)</a>~si", $html, $m);

        // Build the element
        $dokType = (int)$m[1][0];
        $el      = [
            "type"  => static::TYPE_LINK_PAGE,
            "id"    => (int)$m[2][0],
            "href"  => $m[3][0],
            "title" => $m[4][0],
        ];

        // Handle spacer
        if ($dokType === 199) {
            $el["type"] = static::TYPE_LINK_SPACER;
            $el["href"] = null;
        }

        // Handle external link
        if ($dokType === 3) {
            $isExternal = ! GeneralUtility::isOnCurrentHost($el["slug"]);
            if ($isExternal) {
                $el["type"] = static::TYPE_LINK_EXTERNAL;
            } else {
                $el["type"] = static::TYPE_LINK_INTERNAL;
            }
        }

        // Fetch additional fields if required
        if (! empty($additionalFields)) {
            $pageInfo = $this->Page->getPageInfo($el["id"]);
            foreach ($additionalFields as $field) {
                $propertyName = Inflector::toCamelBack($field);
                if (isset($pageInfo[$field])) {
                    $el["fields"][$propertyName] = $pageInfo[$field];
                } else {
                    $el["fields"][$propertyName] = null;
                }
            }
        }

        // Done
        return $el;
    }

    /**
     * Internal helper to allow event based filtering of the generated menu data
     *
     * @param   string  $type            The menu type to filter / generate
     * @param   array   $menuDefinition  The prepared typo script definition of the menu
     *
     * @return string
     */
    protected function processMenuDefinition(string $type, array $menuDefinition): string
    {
        // Allow filtering
        $this->EventBus->dispatch(($e = new SiteMenuPreProcessorEvent($menuDefinition, $type)));
        $menuDefinition = $e->getDefinition();
        $render         = $e->isRender();

        // Render the menu
        $menu = "";
        if ($render) {
            $menu = $this->TypoScript->renderContentObject("HMENU", $menuDefinition);
        }

        // Allow filtering
        $this->EventBus->dispatch(($e = new SiteMenuHtmlFilterEvent($menuDefinition, $type, $menu)));
        $menu = $e->getMenu();

        // Done
        return $menu;
    }

    /**
     * Internal helper to allow the event based post processing to occur.
     *
     * @param   string  $type     The menu type to filter
     * @param   array   $menu     The generated menu array
     * @param   array   $options  The given options for this menu
     *
     * @return array
     */
    protected function runPostProcessing(string $type, array $menu, array $options): array
    {
        // Check if we have a post processor
        if (! empty($options["postProcessor"]) && class_exists($options["postProcessor"])) {
            /** @var \LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuPostProcessorInterface $processor */
            $processor = $this->getInstanceOf($options["postProcessor"]);
            $menu      = $processor->process($this->key, $menu, $options, $type);
        }

        // Allow event based processing
        $this->EventBus->dispatch(($e = new SiteMenuPostProcessorEvent($this->key, $menu, $type, $options)));

        return $e->getMenu();
    }
}
