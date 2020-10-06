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
 * Last modified: 2020.10.05 at 12:29
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Strategy;


use League\Fractal\Serializer\JsonApiSerializer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class Typo3AwareJsonApiSerializer extends JsonApiSerializer
{
    /**
     * @var string|\TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    protected $language;

    /**
     * @inheritDoc
     */
    public function __construct(SiteLanguage $language, $baseUrl = null)
    {
        $this->language = $language;
        parent::__construct($baseUrl);
    }

    /**
     * @inheritDoc
     */
    public function item($resourceKey, array $data)
    {
        $item = parent::item($resourceKey, $data);
        if (isset($item['data']['links'])) {
            $this->translateLinks($item['data']['links']);
        }

        return $item;
    }

    /**
     * @inheritDoc
     */
    public function meta(array $meta)
    {
        $meta = parent::meta($meta);
        if (isset($meta['links'])) {
            $this->translateLinks($meta['links']);
        }

        return $meta;
    }

    /**
     * @inheritDoc
     */
    protected function fillRelationships($data, $relationships)
    {
        $relationships = parent::fillRelationships($data, $relationships);
        if (isset($relationships['data']['relationships']) && is_array($relationships['data']['relationships'])) {
            foreach ($relationships['data']['relationships'] as &$relationship) {
                if (is_array($relationship['links'])) {
                    $this->translateLinks($relationship['links']);
                }
            }
        }

        return $relationships;
    }

    /**
     * Makes sure that the L= parameter is attached to all generated links, so translations are handled properly
     *
     * @param $links
     */
    protected function translateLinks(&$links): void
    {
        if (! is_array($links) || $this->language->getLanguageId() <= 0) {
            return;
        }

        $languageBase         = (string)$this->language->getBase();
        $languageBaseRelative = rtrim($this->language->getBase()->getPath(), '/');
        $languageId           = $this->language->getLanguageId();
        foreach ($links as &$link) {
            $hasQuery = strpos($link, '?') !== false;

            // L already exists
            if ($hasQuery & strpos($link, 'L=') !== false) {
                continue;
            }

            // Has language base
            if (stripos($link, $languageBase) || preg_match('~' . preg_quote($languageBaseRelative) . '(/|$)~', $link)) {
                continue;
            }


            $link .= ($hasQuery ? '&' : '?') . 'L=' . $languageId;
        }
    }

}
