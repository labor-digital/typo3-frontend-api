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
 * Last modified: 2019.09.18 at 12:12
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer;


use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;

class PageTransformer extends AbstractResourceTransformer
{

    /**
     * Prepare the list of available includes
     *
     * @var array
     */
    protected $availableIncludes = ["data", "content", "common", "translation", "pidConfig"];

    /**
     * Register all default includes
     *
     * @var array
     */
    protected $defaultIncludes = ["pidConfig"];

    /**
     * @inheritDoc
     */
    protected function transformValue($value): array
    {
        if (! $value instanceof Page) {
            return [];
        }

        // Check if the frontend requires the new translation knows this translation
        if (! in_array($value->getLanguageCode(), $value->getLoadedLanguageCodes())) {
            $this->defaultIncludes[] = "translation";
        }

        // Check if we have to render the common elements, because we changed the layout
        // or check if common elements have to be refreshed
        if ($value->isLayoutChange() || ! empty($value->getRefreshCommon())) {
            $this->defaultIncludes[] = "common";
        }

        // Convert the page object itself
        return [
            "id"            => $value->getId(),
            "siteUrl"       => $value->getSiteUrl(),
            "siteLanguages" => $value->getLanguageCodes(),
            "languageCode"  => $value->getLanguageCode(),
            "pageLayout"    => $value->getPageLayout(),
            "rootLine"      => $value->getRootLine(),
            "links"         => $value->getLinks(),
            "isPreview"     => $this->Tsfe()->getTsfe()->fePreview === 1,
        ];
    }

    /**
     * Allow the inclusion of the page pid configuration object
     *
     * @param $value
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includePidConfig($value)
    {
        if (! $value instanceof Page) {
            return $this->null();
        }

        return $this->autoIncludeItem($value->getPagePidConfig());
    }

    /**
     * Allow the inclusion of the page data object
     *
     * @param $value
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeData($value)
    {
        if (! $value instanceof Page) {
            return $this->null();
        }

        return $this->autoIncludeItem($value->getPageData());
    }

    /**
     * Allow the inclusion of the page layout of content elements on this page
     *
     * @param $value
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeContent($value)
    {
        if (! $value instanceof Page) {
            return $this->null();
        }

        return $this->autoIncludeItem($value->getPageContents());
    }

    /**
     * Allows the inclusion of common content elements for this page
     *
     * @param $value
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeCommon($value)
    {
        if (! $value instanceof Page) {
            return $this->null();
        }

        return $this->autoIncludeCollection($value->getCommonElements());
    }

    /**
     * Allows inclusion of the global translation labels
     *
     * @param $value
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeTranslation($value)
    {
        if (! $value instanceof Page) {
            return $this->null();
        }

        return $this->autoIncludeItem($value->getPageTranslation());
    }
}
