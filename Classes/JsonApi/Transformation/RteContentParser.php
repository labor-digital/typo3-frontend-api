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
 * Last modified: 2020.03.27 at 13:15
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation;


use LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator;
use LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService;
use Neunerlei\Arrays\Arrays;
use Neunerlei\FileSystem\Fs;
use TYPO3\CMS\Core\SingletonInterface;

class RteContentParser implements SingletonInterface {
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService
	 */
	protected $typoScriptService;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator
	 */
	protected $simulator;
	
	/**
	 * The prepared parser configuration or null if we don't have it loaded yet
	 * @var array|null
	 */
	protected $preparedParserConfig;
	
	/**
	 * RteContentParser constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService    $typoScriptService
	 * @param \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator $simulator
	 */
	public function __construct(TypoScriptService $typoScriptService, EnvironmentSimulator $simulator) {
		$this->typoScriptService = $typoScriptService;
		$this->simulator = $simulator;
	}
	
	/**
	 * Parses the content of an RTE field from the database to a valid frontend output
	 * It utilizes the frontend parseFunc you know from fluid_styled_contents with all typo script constants
	 * being applied as usual.
	 *
	 * @param string $content The content to be parsed
	 *
	 * @return string The parsed content
	 */
	public function parseContent(string $content): string {
		// Check if we have the config already loaded
		if (is_null($this->preparedParserConfig))
			$this->preparedParserConfig = $this->loadParserConfig();
		// Parse the string using the simulator
		return (string)$this->simulator->runWithEnvironment(["ignoreIfFrontendExists"], function () use ($content) {
			return $this->typoScriptService->Tsfe
				->getContentObjectRenderer()->parseFunc($content, $this->preparedParserConfig);
		});
	}
	
	/**
	 * Loads the parser configuration either directly from typo script, imports it from the fluid_styled_content
	 * extension or uses the internal shipped fallback if we can't find neither of both
	 *
	 * @return array
	 */
	protected function loadParserConfig(): array {
		
		// Try to load the configuration from typo script
		$config = $this->typoScriptService->get("lib.parseFunc_RTE");
		if (is_array($config)) return $config;
		
		// Load the required constants
		$constantDefaults = [
			"styles.content.links.keep"      => "path",
			"styles.content.links.extTarget" => "_blank",
			"styles.content.allowTags"       => "x, a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, s, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var",
		];
		$constants = [];
		foreach ($constantDefaults as $path => $default)
			$constants["{\$$path}"] = $this->typoScriptService->getConstants($path, [
				"pid"     => $this->simulator->TypoContext->getPidAspect()->getCurrentPid(),
				"default" => $default,
			]);
		
		// Check if we can use the typo script shipped with fluid styled content
		try {
			$path = $this->simulator->TypoContext->getPathAspect()->typoPathToRealPath("EXT:fluid_styled_content/Configuration/TypoScript/Helper/ParseFunc.typoscript");
			if (Fs::exists($path . "foo")) {
				$content = Fs::readFile($path);
				$content = str_replace(array_keys($constants), array_values($constants), $content);
				$ts = $this->typoScriptService->parse($content);
				$config = Arrays::getPath($ts, ["lib.", "parseFunc_RTE."]);
				if (is_array($config)) return $config;
			}
		} catch (\Throwable $e) {
		}
		
		// Load the fallback file
		$content = Fs::readFile(__DIR__ . DIRECTORY_SEPARATOR . "RteContentParserFallbackParseFunc.typoscript");
		$content = str_replace(array_keys($constants), array_values($constants), $content);
		$ts = $this->typoScriptService->parse($content);
		return Arrays::getPath($ts, ["lib.", "parseFunc_RTE."], []);
	}
}