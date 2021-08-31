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
use LaborDigital\Typo3BetterApi\Tsfe\TsfeService;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService;
use Neunerlei\FileSystem\Fs;
use Throwable;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class RteContentParser implements SingletonInterface
{

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator
     */
    protected $simulator;

    /**
     * @var \LaborDigital\Typo3BetterApi\Tsfe\TsfeService
     */
    protected $tsfeService;

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $typoContext;

    /**
     * The prepared parser configuration or null if we don't have it loaded yet
     *
     * @var array|null
     */
    protected $fallbackParserConfig;

    /**
     * RteContentParser constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService     $typoScriptService
     * @param   \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator  $simulator
     * @param   \LaborDigital\Typo3BetterApi\Tsfe\TsfeService                 $tsfeService
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext          $typoContext
     */
    public function __construct(
        TypoScriptService $typoScriptService,
        EnvironmentSimulator $simulator,
        TsfeService $tsfeService,
        TypoContext $typoContext
    ) {
        $this->typoScriptService = $typoScriptService;
        $this->simulator         = $simulator;
        $this->tsfeService       = $tsfeService;
        $this->typoContext       = $typoContext;
    }

    /**
     * Parses the content of an RTE field from the database to a valid frontend output
     * It utilizes the frontend parseFunc you know from fluid_styled_contents with all typo script constants
     * being applied as usual.
     *
     * @param   string  $content  The content to be parsed
     *
     * @return string The parsed content
     */
    public function parseContent(string $content): string
    {
        // Parse the string using the simulator
        return (string)$this->simulator->runWithEnvironment(['ignoreIfFrontendExists'], function () use ($content) {
            $this->ensureTsParserConfig($this->tsfeService->getTsfe());

            $features = GeneralUtility::makeInstance(Features::class);
            $config   = [
                'htmlSanitize' => (int)$features->isFeatureEnabled('security.frontend.htmlSanitizeParseFuncDefault'),
            ];

            return $this->tsfeService->getContentObjectRenderer()->parseFunc($content, $config, '< lib.parseFunc_RTE');
        });
    }

    /**
     * Makes sure that both lib.parseFunc and lib.parseFunc_RTE are set up correctly
     * to parse our contents with
     *
     * @param   \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController  $tsfe
     */
    protected function ensureTsParserConfig(TypoScriptFrontendController $tsfe): void
    {
        // Inject parseFunc if required
        if (! isset($tsfe->tmpl->setup['lib.']['parseFunc.'])) {
            $config                                  = $this->getFallbackParserConfig();
            $tsfe->tmpl->setup['lib.']['parseFunc.'] = $config['lib.']['parseFunc.'] ?? [];
        }

        // Inject parseFunc_RTE if required
        if (! isset($tsfe->tmpl->setup['lib.']['parseFunc_RTE.'])) {
            $config                                      = $this->getFallbackParserConfig();
            $tsfe->tmpl->setup['lib.']['parseFunc_RTE.'] = $config['lib.']['parseFunc_RTE.'] ?? [];
        }
    }

    /**
     * Loads the parser configuration either directly from typo script, imports it from the fluid_styled_content
     * extension or uses the internal shipped fallback if we can't find neither of both
     *
     * @return array
     */
    protected function getFallbackParserConfig(): array
    {
        if (isset($this->fallbackParserConfig)) {
            return $this->fallbackParserConfig;
        }

        // Load the required constants
        $constantDefaults = [
            'styles.content.links.keep'      => 'path',
            'styles.content.links.extTarget' => '_blank',
            'styles.content.allowTags'       => 'x, a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, s, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
        ];
        $constants        = [];
        foreach ($constantDefaults as $path => $default) {
            $constants['{$' . $path . '}'] = $this->typoScriptService->getConstants($path, [
                'pid'     => $this->typoContext->Pid()->getCurrent(),
                'default' => $default,
            ]);
        }

        // Check if we can use the typo script shipped with fluid styled content
        try {
            $path = $this->typoContext->Path()->typoPathToRealPath('EXT:fluid_styled_content/Configuration/TypoScript/Helper/ParseFunc.typoscript');
            if (Fs::exists($path)) {
                $content = Fs::readFile($path);
                $content = str_replace(array_keys($constants), array_values($constants), $content);

                return $this->fallbackParserConfig = $this->typoScriptService->parse($content);
            }
        } catch (Throwable $e) {
        }

        // Load the fallback file
        $content = Fs::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'RteContentParserFallbackParseFunc.typoscript');
        $content = str_replace(array_keys($constants), array_values($constants), $content);

        return $this->fallbackParserConfig = $this->typoScriptService->parse($content);
    }
}
