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
 * Last modified: 2019.09.19 at 10:02
 */

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3FrontendApi\FrontendApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageMenu;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use Neunerlei\Options\Options;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class SiteConfigurator {
	
	/**
	 * The default options for the menu generation
	 * @var array
	 */
	protected $menuDefaultOptions;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
	 */
	protected $context;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
	 */
	protected $config;
	
	/**
	 * SiteConfigurator constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext      $context
	 * @param \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig $config
	 */
	public function __construct(ExtConfigContext $context, SiteConfig $config) {
		$this->context = $context;
		$this->config = $config;
		$this->menuDefaultOptions = [
			"entryLevel"       => [
				"type"    => "int",
				"default" => 0,
			],
			"excludeUidList"   => [
				"type"      => "array",
				"default"   => [],
				"preFilter" => function ($v) {
					return $this->convertPids($v);
				},
			],
			"includeNotInMenu" => [
				"type"    => "bool",
				"default" => FALSE,
			],
			"levels"           => [
				"type"    => "int",
				"default" => 2,
			],
			"additionalFields" => [
				"type"    => "array",
				"default" => [],
			],
			"loadForLayouts"   => [
				"type"    => "array",
				"default" => [],
			],
			"postProcessor"    => [
				"type"      => ["string", "null"],
				"default"   => NULL,
				"validator" => function (?string $class) {
					if (is_null($class)) return TRUE;
					if (!class_exists($class)) return "The given post processor class: \"$class\" does not exist!";
					if (!in_array(PageMenuPostProcessorInterface::class, class_implements($class)))
						return "The given post processor \"$class\" must implement the required interface: " .
							PageMenuPostProcessorInterface::class;
					return TRUE;
				},
			],
		];
	}
	
	/**
	 * Returns the site identifier for this configuration.
	 * Returns null if this is the global site
	 * @return string|null
	 */
	public function getSiteIdentifier(): ?string {
		if ($this->config->siteIdentifier === 0) return NULL;
		return $this->config->siteIdentifier;
	}
	
	/**
	 * Registers a new translation file for the frontend translation.
	 *
	 * @param string $file This can be either a fully qualified path an EXT:... path or just the
	 *                     translation context you registered
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	public function registerTranslationFile(string $file): SiteConfigurator {
		$labels = $this->context->Translation->getAllKeysInFile($this->context->replaceMarkers($file));
		$this->config->translationLabels = array_merge($this->config->translationLabels, $labels);
		return $this;
	}
	
	/**
	 * Renders a typoScript element using the TSFE and provides it as a "common element" for the pages
	 *
	 * @param string $key                   A unique key that identifies this object. Note that
	 *                                      all common elements and menus share the same namespace
	 *                                      when the objects are passed to the frontend.
	 * @param string $typoScriptObjectPath  The path to the typoScript object you want to render.
	 *                                      Probably something like lib.myObject
	 * @param array  $loadForLayouts        Can be used to define the keys of layouts which should load this element.
	 *                                      If not given, this element will be auto-loaded on all pages.
	 *                                      You may use "default" to load this element in the default layout
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	public function registerCommonTypoScriptElement(string $key, string $typoScriptObjectPath, array $loadForLayouts = []): SiteConfigurator {
		return $this->addToCommonElements("ts", $key, $loadForLayouts, $typoScriptObjectPath);
	}
	
	/**
	 * Registers an element of the tt_content table as a common element and provides it as a "common element" for the
	 * pages
	 *
	 * @param string $key            A unique key that identifies this object. Note that
	 *                               all common elements and menus share the same namespace
	 *                               when the objects are passed to the frontend.
	 * @param int    $elementUid     The uid of the element in the tt_content table
	 * @param array  $loadForLayouts Can be used to define the keys of layouts which should load this element.
	 *                               If not given, this element will be auto-loaded on all pages. You may use "default"
	 *                               to load this element in the default layout
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	public function registerCommonContentElement(string $key, int $elementUid, array $loadForLayouts = []): SiteConfigurator {
		return $this->addToCommonElements("contentElement", $key, $loadForLayouts, $elementUid);
	}
	
	/**
	 * Registers a custom class as a common element handler. The class will be instantiated and executed
	 * when the element is requested via the api
	 *
	 * @param string $key            A unique key that identifies this object. Note that
	 *                               all common elements and menus share the same namespace
	 *                               when the objects are passed to the frontend.
	 * @param string $class          The class that should be used to generate the data for this element.
	 *                               The class has to implement the CommonCustomElementInterface
	 * @param array  $data           Optional data that will be passed to the element class.
	 *                               WARNING: The data MUST BE serializable!
	 * @param array  $loadForLayouts Can be used to define the keys of layouts which should load this element.
	 *                               If not given, this element will be auto-loaded on all pages. You may use "default"
	 *                               to load this element in the default layout
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
	 * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\CommonCustomElementInterface
	 */
	public function registerCommonCustomElement(string $key, string $class, array $data = [], array $loadForLayouts = []): SiteConfigurator {
		if (!in_array(CommonCustomElementInterface::class, class_implements($class)))
			throw new FrontendApiException("The given class: \"$class\" has to implement the required interface: " . CommonCustomElementInterface::class);
		
		return $this->addToCommonElements("custom", $key, $loadForLayouts, [
			"class" => $class,
			"data"  => $data,
		]);
	}
	
	/**
	 * Can be used to change the class to represent the page data.
	 *
	 * @param string $class
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function setPageDataClass(string $class): SiteConfigurator {
		if (!class_exists($class)) throw new JsonApiException("The given page data model $class does not exist!");
		if (!in_array(AbstractEntity::class, class_parents($class)))
			throw new JsonApiException("The given page data model $class has to extend the " . AbstractEntity::class . " class!");
		$this->config->pageDataClass = $class;
		return $this;
	}
	
	/**
	 * Can be used to change which field is used to define the layout of the frontend page.
	 * By default this is "backend_layout", but it can be any field of the pages table.
	 *
	 * @param string $field
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	public function setPageLayoutField(string $field): SiteConfigurator {
		$this->config->pageLayoutField = $field;
		return $this;
	}
	
	/**
	 * By default the api sends a 15 minute browser cache "Expires" header.
	 * If you want to modify the value you can just set the ttl in seconds here.
	 * If you set this to 0 you will completely disable all caching!
	 *
	 * @param int $ttl
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	public function setBrowserCacheTtl(int $ttl): SiteConfigurator {
		$this->config->browserCacheTtl = $ttl;
		return $this;
	}
	
	/**
	 * Registers a new page menu for this site
	 *
	 * @param string $key     A unique key to identify this menu with. Note that
	 *                        all common elements and menus share the same namespace
	 *                        when the objects are passed to the frontend.
	 * @param array  $options The options to build this menu with
	 *                        - entryLevel int(0): Defines at which level in the rootLine the menu should start.
	 *                        Default is “0” which gives us a menu of the very first pages on the site.
	 *                        - excludeUidList array: A list of uid's that should be excluded from the menu
	 *                        - includeNotInMenu bool (FALSE): If set to true the menu will include pages
	 *                        that are marked as "don't show in menu"
	 *                        - additionalFields array: An optional list of additional database fields to fetch
	 *                        and append to each menu item
	 *                        - levels int(2): The number of levels we should render the nested menus recursively.
	 *                        - showSpacers bool(FALSE): If set to true the "spacers" inside a menu will also be
	 *                        rendered to the resulting array with a type of "spacer" instead of "link"
	 *                        - loadForLayouts array: Can be used to define the keys of layouts which should load this
	 *                        element. If not given, this element will be auto-loaded on all pages. You may use
	 *                        "default" to load this element in the default layout
	 *                        - postProcessor string: A class that can be used to filter/alter the menu array
	 *                        before it is passed to the frontend api. Has to implement the
	 *                        PageMenuPostProcessorInterface
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuPostProcessorInterface
	 */
	public function registerPageMenu(string $key, array $options = []): SiteConfigurator {
		// Prepare options
		$optionDefinition = $this->menuDefaultOptions;
		$optionDefinition["showSpacers"] = [
			"type"    => "bool",
			"default" => FALSE,
		];
		$options = Options::make($options, $optionDefinition);
		
		// Store the menu
		return $this->addToCommonElements("menu", $key, $options["loadForLayouts"], [
			"type"    => PageMenu::TYPE_MENU_PAGE,
			"options" => $options,
		]);
	}
	
	/**
	 * Registers a new root line / breadcrumb menu for this site
	 *
	 * @param string $key     A unique key to identify this menu with
	 * @param array  $options The options to build this menu with
	 *                        - offsetStart int (0): The offset from the start of the root line
	 *                        - offsetEnd int(0): The offset from the end of the root line.
	 *                        - entryLevel int(0): Defines at which level in the rootLine the menu should start. Default
	 *                        is “0” which gives us a menu of the very first pages on the site.
	 *                        - excludeUidList array: A list of uid's that should be excluded from the menu
	 *                        - includeNotInMenu bool (FALSE): If set to true the menu will include pages
	 *                        that are marked as "don't show in menu"
	 *                        - additionalFields array: An optional list of additional database fields to fetch
	 *                        and append to each menu item
	 *                        - loadForLayouts array: Can be used to define the keys of layouts which should load this
	 *                        element. If not given, this element will be auto-loaded on all pages. You may use
	 *                        "default" to load this element in the default layout
	 *                        - postProcessor string: A class that can be used to filter/alter the menu array
	 *                        before it is passed to the frontend api. Has to implement the
	 *                        PageMenuPostProcessorInterface
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuPostProcessorInterface
	 */
	public function registerRootLineMenu(string $key, array $options = []): SiteConfigurator {
		// Prepare options
		$optionDefinition = $this->menuDefaultOptions;
		unset($optionDefinition["levels"]);
		$optionDefinition["offsetStart"] = [
			"type"    => "int",
			"default" => 0,
		];
		$optionDefinition["offsetEnd"] = [
			"type"    => "int",
			"default" => 0,
		];
		$options = Options::make($options, $optionDefinition);
		
		// Store the menu
		return $this->addToCommonElements("menu", $key, $options["loadForLayouts"], [
			"type"    => PageMenu::TYPE_MENU_ROOT_LINE,
			"options" => $options,
		]);
	}
	
	/**
	 * Registers a new directory menu to this site
	 *
	 * @param string     $key     A unique key to identify this menu with
	 * @param int|string $pid     The pid to use as directory root. It defines which pages we should render
	 * @param array      $options The options to build this menu with
	 *                            - entryLevel int(0): Defines at which level in the rootLine the menu should start.
	 *                            Default is “0” which gives us a menu of the very first pages on the site.
	 *                            - excludeUidList array: A list of uid's that should be excluded from the menu
	 *                            - includeNotInMenu bool (FALSE): If set to true the menu will include pages
	 *                            that are marked as "don't show in menu"
	 *                            - additionalFields array: An optional list of additional database fields to fetch
	 *                            and append to each menu item
	 *                            - levels int(1): The number of levels we should render the nested menus recursively.
	 *                            - showSpacers bool(FALSE): If set to true the "spacers" inside a menu will also be
	 *                            rendered to the resulting array with a type of "spacer" instead of "link"
	 *                            - loadForLayouts array: Can be used to define the keys of layouts which should load
	 *                            this element. If not given, this element will be auto-loaded on all pages. You may
	 *                            use "default" to load this element in the default layout
	 *                            - postProcessor string: A class that can be used to filter/alter the menu array
	 *                            before it is passed to the frontend api. Has to implement the
	 *                            PageMenuPostProcessorInterface
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuPostProcessorInterface
	 */
	public function registerDirectoryMenu(string $key, $pid, array $options = []): SiteConfigurator {
		// Prepare options
		$optionDefinition = $this->menuDefaultOptions;
		$optionDefinition["showSpacers"] = [
			"type"    => "bool",
			"default" => FALSE,
		];
		$optionDefinition["pid"] = [
			"type"      => "int",
			"default"   => $pid,
			"preFilter" => function ($v) {
				return $this->convertPids($v);
			},
		];
		$optionDefinition["levels"]["default"] = 1;
		$options = Options::make($options, $optionDefinition);
		
		// Store the menu
		return $this->addToCommonElements("menu", $key, $options["loadForLayouts"], [
			"type"    => PageMenu::TYPE_MENU_DIRECTORY,
			"options" => $options,
		]);
	}
	
	/**
	 * Can be used to set the path to the static html template which is displayed for all
	 * requests that don't target the api entry point.
	 *
	 * @param string $templatePath Either a path beginning with EXT:... or an absolute path to a file
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	public function registerStaticTemplate(?string $templatePath): SiteConfigurator {
		$this->config->staticTemplate = NULL;
		if (!empty($templatePath))
			$this->config->staticTemplate = $this->context->TypoContext->getPathAspect()->typoPathToRealPath(
				$this->context->replaceMarkers($templatePath)
			);
		return $this;
	}
	
	/**
	 * Can be used to add additional fields to the root line that is added to every page request of the api.
	 *
	 * @param array $fields
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	public function registerAdditionalRootLineFields(array $fields): SiteConfigurator {
		$this->config->additionalRootLineFields = array_unique(array_merge($this->config->additionalRootLineFields, $fields));
		return $this;
	}
	
	/**
	 * Used to register a root line data provider. Data providers are called once for every entry in the root line.
	 * They receive the already prepared root line entry and can additional, dynamic data you can't implement
	 * using just raw database fields.
	 *
	 * The class has to implement the RootLineDataProviderInterface interface
	 *
	 * @param string $class
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
	 * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\RootLineDataProviderInterface
	 */
	public function registerRootLineDataProvider(string $class): SiteConfigurator {
		if (!in_array(RootLineDataProviderInterface::class, class_implements($class)))
			throw new FrontendApiException("The given root line data provider class \"$class\" has to implement the required interface: " . RootLineDataProviderInterface::class);
		$this->config->rootLineDataProviders[$class] = $class;
		return $this;
	}
	
	/**
	 * Returns the raw configuration object
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
	 */
	public function getConfig(): SiteConfig {
		return $this->config;
	}
	
	/**
	 * Internal helper to pre-filter the pid's in option values
	 *
	 * @param $pids
	 *
	 * @return array|mixed|string
	 */
	protected function convertPids($pids) {
		$pidAspect = $this->context->TypoContext->getPidAspect();
		if (is_string($pids) && $pidAspect->hasPid($pids)) return $pidAspect->getPid($pids);
		if (is_array($pids))
			foreach ($pids as $k => $pid)
				if (is_string($pid) && $pidAspect->hasPid($pid)) $pids[$k] = $pidAspect->getPid($pid);
		return $pids;
	}
	
	/**
	 * Internal helper to add a new common element definition
	 *
	 * @param string $type
	 * @param string $key
	 * @param array  $loadForLayouts
	 * @param        $value
	 *
	 * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurator
	 */
	protected function addToCommonElements(string $type, string $key, array $loadForLayouts, $value): SiteConfigurator {
		if (empty($loadForLayouts)) $loadForLayouts = ["*"];
		foreach ($loadForLayouts as $layout)
			$this->config->commonElements[$layout][$key] = [
				"type"  => $type,
				"value" => $value,
			];
		return $this;
	}
}