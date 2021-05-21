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
 * Last modified: 2020.01.17 at 15:53
 */

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractChildExtConfigOption;
use LaborDigital\Typo3FrontendApi\FrontendApiException;

class FrontendApiSiteOption extends AbstractChildExtConfigOption
{

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiOption
     */
    protected $parent;

    /**
     * Holds the raw configuration while we collect the options
     *
     * @var array
     */
    protected $config
        = [
            "enabled" => false,
        ];

    /**
     * Returns true if there is a site configuration and with that the SPA mode enabled
     *
     * @return bool
     */
    public function isSpaModeEnabled(): bool
    {
        return $this->config["enabled"];
    }

    /**
     * Site configuration is used when you create a whole site as SPA app. It is basically your layout and
     * global data bridge to the frontend. It can be used to register global layout "content elements", menus,
     * as well as global translation files.
     *
     * You can either set a site configuration that applies to all typo3 "sites", or can specify exactly
     * which site you want to target with the configuration. Note that all configuration's that apply to a certain
     * site will always have priority over the "global" configuration. Also note, that site based configurations
     * do NOT extend the "global" configuration, but are completely independent.
     *
     * You can always just have a single site configuration class for a single site.
     *
     * @param   string       $siteConfigClass  The configuration class to set. The class has to implement the
     *                                         SiteConfigurationInterface
     * @param   string|null  $siteIdentifier   If this is left empty, the configuration will apply to all typo3 sites.
     *                                         You can supply a valid typo3 "site" identifier to limit the configuration
     *                                         to that site.
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\FrontendApiSiteOption
     * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
     * @see \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfigurationInterface
     */
    public function setSiteConfig(string $siteConfigClass, ?string $siteIdentifier = null): FrontendApiSiteOption
    {
        // Check if there is already a hybrid app registered
        if ($this->parent->hybrid()->isHybridModeEnabled()) {
            throw new FrontendApiException("You can set a site config, because there is already a hybrid configuration present!");
        }
        $this->config["enabled"] = true;
        $siteIdentifier          = is_null($siteIdentifier) ? 0 : $siteIdentifier;

        return $this->addToCachedValueConfig("siteConfig", $siteConfigClass, $siteIdentifier);
    }

    /**
     * Removes the registered site configuration class for the site with the given identifier.
     *
     * @param   string|null  $siteIdentifier  Either the identifier of the typo3 "site" to remove the config for,
     *                                        if empty the global site config will be removed.
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\FrontendApiSiteOption
     */
    public function removeSiteConfig(?string $siteIdentifier): FrontendApiSiteOption
    {
        $siteIdentifier = is_null($siteIdentifier) ? 0 : $siteIdentifier;
        $return         = $this->removeFromCachedValueConfig("siteConfig", $siteIdentifier);
        if (empty($this->getCachedValueConfig("siteConfig"))) {
            $this->config["enabled"] = false;
        }

        return $return;
    }

    /**
     * Internal helper to fill the main config repository' config array with the local configuration
     *
     * @param   array  $config
     */
    public function __buildConfig(array &$config): void
    {
        $config["site"] = $this->runCachedValueGenerator("siteConfig", SiteConfigGenerator::class);
    }
}
