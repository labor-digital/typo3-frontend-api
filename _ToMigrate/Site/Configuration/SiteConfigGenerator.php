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
 * Last modified: 2019.09.19 at 10:03
 */

namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigException;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\CachedValueGeneratorInterface;
use LaborDigital\Typo3FrontendApi\Event\SiteConfigFilterEvent;

class SiteConfigGenerator implements CachedValueGeneratorInterface
{
    /**
     * Is responsible to build the site configuration list and returns them as serialized string list
     *
     * @param   array                                                    $data
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext  $context
     * @param   array                                                    $additionalData
     * @param                                                            $option
     *
     * @return array
     */
    public function generate(array $data, ExtConfigContext $context, array $additionalData, $option): array
    {
        $configurations = [];

        // Add the default site configuration if we don't have one
        if (! isset($data[0])) {
            $data[0] = [
                "value"  => DefaultSiteConfigFallback::class,
                "extKey" => "typo3_frontend_api",
                "vendor" => "LaborDigital",
            ];
        }

        // Handle the configured configurations
        $context->runWithCachedValueDataScope($data, function (string $configClass, $siteIdentifier) use ($context, &$configurations) {
            // Validate the class
            if (! class_exists($configClass)) {
                throw new ExtConfigException("The given site configuration class $configClass does not exist!");
            }
            if (! in_array(SiteConfigurationInterface::class, class_implements($configClass))) {
                throw new ExtConfigException("The given site configuration class $configClass does not implement the required interface: " .
                                             SiteConfigurationInterface::class);
            }

            // Create the configurator
            $configurator = $context->getInstanceOf(SiteConfigurator::class, [$context]);

            // Get the config
            $config                 = $configurator->getConfig();
            $config->siteIdentifier = $siteIdentifier;

            // Pass it through the configuration class
            call_user_func([$configClass, "configureSite"], $configurator, $context);

            // Make sure the global, common elements are available for all layouts
            if (! isset($config->commonElements["default"])) {
                $config->commonElements["default"] = [];
            }
            if (! empty($config->commonElements) && ! empty($config->commonElements["*"])) {
                foreach ($config->commonElements["*"] as $k => $v) {
                    foreach ($config->commonElements as $layout => &$el) {
                        if ($layout !== "*") {
                            $el[$k] = $v;
                        }
                    }
                }
            }
            unset($config->commonElements["*"]);

            // Allow filtering
            $context->EventBus->dispatch(($e = new SiteConfigFilterEvent($config, $configClass, $context)));

            // Done
            $configurations[$siteIdentifier] = serialize($config);
        });

        return $configurations;
    }
}
