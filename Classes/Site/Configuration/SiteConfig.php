<?php
declare(strict_types=1);
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
 * Last modified: 2019.09.19 at 10:35
 */

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Model\PageDataModel;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\CommonElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteConfig
{

    /**
     * The site identifier this site should react to
     *
     * @var string|int
     */
    public $siteIdentifier;

    /**
     * The class to use as page data model
     *
     * @var string
     */
    public $pageDataClass = PageDataModel::class;

    /**
     * A list of database fields that should be inherited from the parent pages if their current value is empty
     *
     * @var array
     */
    public $pageDataSlideFields = [];

    /**
     * Defines which field is used to define the layout of the frontend page.
     * By default this is "backend_layout", but it can be any field of the pages table.
     *
     * @var string
     */
    public $pageLayoutField = 'backend_layout';

    /**
     * A list of short translation labels and their matching typo3 translation keys
     *
     * @var array
     */
    public $translationLabels = [];

    /**
     * A list of content elements and typoScript elements that are shared between multiple pages
     * and are considered "layout" elements
     *
     * @var array
     */
    public $commonElements = [];

    /**
     * If set this holds the path to the static html template which is displayed for all
     * requests that don't target the api entry point
     *
     * @var string|null
     */
    public $staticTemplate;

    /**
     * Additional fields that should be added to the root line array of the api result
     *
     * @var array
     */
    public $additionalRootLineFields = [];

    /**
     * A list of classes that provide additional data for the site's root line definition
     *
     * @var string[]
     * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\RootLineDataProviderInterface
     */
    public $rootLineDataProviders = [];

    /**
     * The time to live for the browser cache "Expire" tags.
     * If this is 0 There will be no browser caching!
     *
     * @var float|int
     * @deprecated will be removed in v10 -> has no actual use anymore
     */
    public $browserCacheTtl = 15 * 60;

    /**
     * The list of registered site link provider classes.
     * The classes are used to generate static links that will be provided to the frontend
     *
     * @var array
     * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteLinkProviderInterface
     */
    public $linkProviders = [];

    /**
     * Generates the list of all common element instances for this site.
     *
     * @param   string  $layout         The layout key to find the elements for
     * @param   array   $requestedKeys  A list of keys that is used to filter the page objects
     *
     * @return array
     */
    public function getCommonElementInstances(string $layout, array $requestedKeys = []): array
    {
        $collection = [];

        if (empty($this->commonElements[$layout])) {
            $layout = 'default';
        }

        foreach ($this->commonElements[$layout] as $key => $foo) {
            if (! empty($requestedKeys) && ! in_array($key, $requestedKeys, true)) {
                continue;
            }
            $collection[] = GeneralUtility::makeInstance(CommonElement::class, $layout, $key);
        }

        return $collection;
    }
}
