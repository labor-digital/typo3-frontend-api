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
 * Last modified: 2020.10.05 at 20:07
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page;


use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use Neunerlei\PathUtil\Path;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Seo\Canonical\CanonicalGenerator;

class PageDataLinkGenerator implements SingletonInterface
{
    use FrontendApiContextAwareTrait;

    /**
     * Generates the canonical url for the given page data object
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData  $pageData
     *
     * @return string
     */
    public function makeCanonicalUrl(PageData $pageData): string
    {
        $context = $this->FrontendApiContext();

        if (! class_exists(CanonicalGenerator::class)) {
            return $context->Links()->getLink()->build();
        }

        $canonicalTag = $context->Simulator()->runWithEnvironment(['pid' => $pageData->getId()], static function () use ($context) {
            return $context->getInstanceOf(CanonicalGenerator::class)->generate();
        });

        preg_match('~href="(.*?)"~', $canonicalTag, $m);

        return (string)Path::makeUri($m[1])->withQuery(null);
    }

    /**
     * Generates all required hreflang tags for the given page data object
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData  $pageData
     *
     * @return array
     */
    public function makeHrefLangUrls(PageData $pageData): array
    {
        $context = $this->FrontendApiContext();

        return $context->Simulator()->runWithEnvironment(['pid' => $pageData->getId()],
            function () use ($context) {
                /** @var LanguageMenuProcessor $languageMenu */
                $languageMenu = $context->getInstanceWithoutDi(LanguageMenuProcessor::class);
                $languages    = $languageMenu->process($context->Tsfe()->getContentObjectRenderer(), [], [], []);
                $urls         = [];
                foreach ($languages['languagemenu'] as $language) {
                    if ($language['available'] === 1 && ! empty($language['link'])) {
                        $urls[] = [
                            'rel'      => 'alternative',
                            'hreflang' => $language['hreflang'],
                            'href'     => (string)Path::makeUri($this->getAbsoluteUrl($language['link']))->withQuery(null),
                        ];
                    }
                }

                return $urls;
            });
    }

    /**
     * Forces the given url to be absolute, relative to the current frontend language
     *
     * @param   string  $url
     *
     * @return string
     */
    protected function getAbsoluteUrl(string $url): string
    {
        $uri = new Uri($url);
        if (empty($uri->getHost())) {
            $url = $this->FrontendApiContext()
                        ->TypoContext()
                        ->Language()
                        ->getCurrentFrontendLanguage()
                        ->getBase()
                        ->withPath($uri->getPath());

            if ($uri->getQuery()) {
                $url = $url->withQuery($uri->getQuery());
            }
        }

        return (string)$url;
    }
}
