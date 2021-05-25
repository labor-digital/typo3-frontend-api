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
 * Last modified: 2019.09.20 at 11:40
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource;


use LaborDigital\Typo3BetterApi\Container\CommonDependencyTrait;
use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\CommonElement;
use LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig;

/**
 * Trait SiteConfigAwareTrait
 *
 * @package    LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource
 * @see        \LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait
 * @deprecated will be removed in v10
 */
trait SiteConfigAwareTrait
{
    use CommonServiceLocatorTrait;
    use CommonDependencyTrait {
        CommonDependencyTrait::getInstanceOf insteadof CommonServiceLocatorTrait;
        CommonDependencyTrait::injectContainer insteadof CommonServiceLocatorTrait;
    }

    /**
     * Returns the instance of the config repository
     *
     * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected function ConfigRepository(): FrontendApiConfigRepository
    {
        return $this->getSingletonOf(FrontendApiConfigRepository::class);
    }

    /**
     * Returns the site configuration either for the current site or the global site if no specific site config was
     * found.
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
     */
    protected function getCurrentSiteConfig(): SiteConfig
    {
        return $this->ConfigRepository()->site()->getCurrentSiteConfig();
    }

    /**
     * Generates the list of all page objects for this site.
     *
     * @param   string  $layout         The layout key to find the elements for
     * @param   array   $requestedKeys  A list of keys that is used to filter the page objects
     *
     * @return array
     */
    protected function getCommonElements(string $layout, array $requestedKeys = []): array
    {
        $collection  = [];
        $elementList = $this->ConfigRepository()->site()->getCurrentSiteConfig()->commonElements;
        if (empty($elementList[$layout])) {
            $layout = 'default';
        }
        foreach ($elementList[$layout] as $key => $foo) {
            if (! empty($requestedKeys) && ! in_array($key, $requestedKeys, true)) {
                continue;
            }
            $collection[] = $this->Container()->getWithoutDi(CommonElement::class, [$layout, $key]);
        }

        return $collection;
    }
}