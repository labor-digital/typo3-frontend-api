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
 * Last modified: 2020.10.29 at 23:01
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer;


use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;

class LanguageMenuRenderer extends AbstractMenuRenderer
{
    protected $processorClass = LanguageMenuProcessor::class;

    /**
     * @inheritDoc
     */
    protected function getDefaultMenuTsConfig(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultMenuOptionDefinition(): array
    {
        $filtered = [];
        foreach (parent::getDefaultMenuOptionDefinition() as $key => $def) {
            if (in_array($key, ['loadForLayouts', 'cacheBasedOnQuery', 'postProcessor'], true)) {
                $filtered[$key] = $def;
            }
        }

        return $filtered;
    }


    /**
     * @inheritDoc
     */
    protected function getMenuOptionDefinition(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getMenuTsConfig(array $defaultDefinition): array
    {
        return [
            'as'             => 'menu',
            'languages'      => 'auto',
            'addQueryString' => '0',
        ];
    }

    /**
     * @inheritDoc
     */
    public function render(string $key, array $options): array
    {
        $entries     = parent::render($key, $options);
        $linkService = $this->FrontendApiContext()->Links();
        $site        = $this->FrontendApiContext()->TypoContext()->Site()->getCurrent();
        foreach ($entries as &$entry) {
            $entry['id']           = $entry['twoLetterIsoCode'];
            $entry['isTranslated'] = (bool)$entry['available'];

            if ($entry['available']) {
                $entry['link'] = $linkService->getLink()->withLanguage($entry['languageId'])->build();
            } else {
                $entry['link'] = (string)$site->getLanguageById($entry['languageId'])->getBase();
            }

            unset($entry['locale'], $entry['active'], $entry['current'], $entry['available'], $entry['locale'],
                $entry['twoLetterIsoCode'], $entry['languageId']);

        }

        return $entries;
    }


}
