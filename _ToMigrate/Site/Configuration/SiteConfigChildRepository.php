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
 * Last modified: 2019.09.19 at 10:26
 */

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigChildRepositoryInterface;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\Site\SiteNotConfiguredException;
use TYPO3\CMS\Core\Site\Entity\Site;

class SiteConfigChildRepository implements FrontendApiConfigChildRepositoryInterface
{

    /**
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $context;

    /**
     * The parent repository
     *
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $parent;

    /**
     * SiteConfigChildRepository constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext  $context
     */
    public function __construct(TypoContext $context)
    {
        $this->context = $context;
    }

    /**
     * The list of resolved site configuration objects
     *
     * @var array
     */
    protected $siteConfigurations = [];

    /**
     * Returns the current site object
     *
     * @return \TYPO3\CMS\Core\Site\Entity\Site
     */
    public function getSite(): Site
    {
        return $this->context->getSiteAspect()->getSite();
    }

    /**
     * Returns the configuration object for a site with the given identifier.
     * It throws an exception if the site was not found.
     *
     * @param   string|null  $siteIdentifier  Either the site identifier or null to get the "global" site configuration.
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
     * @throws \LaborDigital\Typo3FrontendApi\Site\SiteNotConfiguredException
     */
    public function getSiteConfig(?string $siteIdentifier): SiteConfig
    {
        $siteIdentifier = is_null($siteIdentifier) ? 0 : $siteIdentifier;
        if (isset($this->siteConfigurations[$siteIdentifier])) {
            return $this->siteConfigurations[$siteIdentifier];
        }
        $configurations = $this->parent->getConfiguration("site");
        if (! isset($configurations[$siteIdentifier])) {
            throw new SiteNotConfiguredException(
                "There is no configuration for the " . ($siteIdentifier === 0 ? "global" : $siteIdentifier) . " site!");
        }

        return $this->siteConfigurations[$siteIdentifier] = unserialize($configurations[$siteIdentifier]);
    }

    /**
     * Similar to getSiteConfig() but automatically falls back to the global site config,
     * if the specific site config was not found.
     *
     * @param   string  $siteIdentifier
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
     */
    public function getSiteOrGlobalConfig(string $siteIdentifier): SiteConfig
    {
        if ($this->hasSiteConfig($siteIdentifier)) {
            return $this->getSiteConfig($siteIdentifier);
        }

        return $this->getSiteConfig(null);
    }

    /**
     * Returns the site configuration either for the current site or the global site if no specific site config was
     * found.
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
     */
    public function getCurrentSiteConfig(): SiteConfig
    {
        $siteIdentifier = $this->context->getSiteAspect()->getSite()->getIdentifier();

        return $this->getSiteOrGlobalConfig($siteIdentifier);
    }

    /**
     * Checks if there is a site configuration for the given site identifier.
     *
     * @param   string|null  $siteIdentifier  Either the identifier for a typo3 "site" or null
     *                                        to check if there is a global configuration.
     *
     * @return bool
     */
    public function hasSiteConfig(?string $siteIdentifier): bool
    {
        try {
            $this->getSiteConfig($siteIdentifier);

            return true;
        } catch (SiteNotConfiguredException $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function __setParentRepository(FrontendApiConfigRepository $parent): void
    {
        $this->parent = $parent;
    }
}
