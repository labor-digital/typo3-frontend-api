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
 * Last modified: 2020.04.01 at 20:42
 */

namespace LaborDigital\Typo3FrontendApi\ExtConfig;

use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\CachedValueGeneratorInterface;
use LaborDigital\Typo3BetterApi\FileAndFolder\ResizedImageOptionsTrait;
use LaborDigital\Typo3FrontendApi\Imaging\Provider\CoreImagingProvider;
use LaborDigital\Typo3FrontendApi\Imaging\Provider\ImagingProviderInterface;
use Neunerlei\Arrays\Arrays;
use Neunerlei\FileSystem\Fs;
use Neunerlei\Options\Options;
use Neunerlei\PathUtil\Path;

class FrontendApiToolConfigGenerator implements CachedValueGeneratorInterface
{
    use ResizedImageOptionsTrait;

    /**
     * @var ExtConfigContext
     */
    protected $context;

    protected const DEFAULT_CONFIG
        = [
            "up"        => [
                "enabled" => false,
            ],
            "scheduler" => [
                "enabled" => false,
            ],
            "imaging"   => null,
        ];

    /**
     * @inheritDoc
     */
    public function generate(array $data, ExtConfigContext $context, array $additionalData, $option)
    {
        $this->context = $context;
        $config        = static::DEFAULT_CONFIG;
        foreach ($data as $k => $options) {
            if ($k === "scheduler") {
                $config[$k] = $this->generateSchedulerConfig($options["value"]);
            } elseif ($k === "imaging") {
                $config[$k] = $this->generateImagingConfig(...$options["value"]);
            } elseif ($k === "up") {
                $config[$k]["enabled"] = $options["value"];
            }
        }

        return $config;
    }

    /**
     * Generates the configuration for the scheduler api endpoint
     *
     * @param   array  $options
     *
     * @return array
     */
    public function generateSchedulerConfig(array $options): array
    {
        return Options::make($options, [
            "enabled"           => [
                "type"    => "bool",
                "default" => true,
            ],
            "maxExecutionType"  => [
                "type"    => "int",
                "default" => 60 * 10,
            ],
            "token"             => [
                "type"   => ["string", "array"],
                "filter" => function ($v) {
                    if (is_string($v)) {
                        return [$v];
                    }

                    return array_values($v);
                },
            ],
            "allowTokenInQuery" => [
                "type"    => "bool",
                "default" => $this->context->TypoContext->getEnvAspect()->isDev(),
            ],
        ]);
    }

    /**
     * Generates the configuration for the imaging endpoint
     *
     * @param   array  $definitions
     * @param   array  $options
     *
     * @return array
     */
    protected function generateImagingConfig(array $definitions, array $options): array
    {
        // Validate the definitions
        if (! isset($definitions["default"])) {
            $definitions["default"] = [];
        }
        foreach ($definitions as $k => $def) {
            $definitions[$k] = $this->applyResizedImageOptions($def);
        }

        // Validate the options
        $options = Options::make($options, [
            "redirectDirectoryPath" => [
                "type"      => "string",
                "validator" => function ($v) {
                    if (! is_dir($v)) {
                        return "The given redirectDirectoryPath does not exist!";
                    }
                    if (! is_readable($v) || ! is_writable($v)) {
                        return "The given redirectDirectoryPath is either not readable or not writable!";
                    }

                    return true;
                },
                "default"   => function () {
                    $dir = Path::join($this->context->TypoContext->getPathAspect()->getVarPath(), "/imaging");
                    Fs::mkdir($dir);

                    return $dir;
                },
                "filter"    => function ($v) {
                    return Path::unifyPath($v);
                },
            ],
            "endpointDirectoryPath" => [
                "type"      => "string",
                "validator" => function ($v) {
                    if (! is_dir($v)) {
                        return "The given endpointDirectoryPath does not exist!";
                    }
                    if (! is_readable($v) || ! is_writable($v)) {
                        return "The given endpointDirectoryPath is either not readable or not writable!";
                    }

                    return true;
                },
                "default"   => function () {
                    $fileadminDir = Arrays::getPath($GLOBALS, "TYPO3_CONF_VARS.BE.fileadminDir", "/fileadmin");
                    $path         = Path::join($this->context->TypoContext->Path()->getPublicPath(), $fileadminDir);
                    if (! file_exists($path)) {
                        // Make sure we don't crash the system
                        $path = sys_get_temp_dir();
                    }

                    return $path;
                },
                "filter"    => function ($v) {
                    return Path::unifyPath($v);
                },
            ],
            "imagingProvider"       => [
                "type"      => "string",
                "validator" => function ($v) {
                    if (! class_exists($v)) {
                        return "The imaging provider class $v does not exist!";
                    }
                    if (! in_array(ImagingProviderInterface::class, class_implements($v))) {
                        return "The imaging provider class $v has to implement the required interface: " . ImagingProviderInterface::class;
                    }

                    return true;
                },
                "default"   => CoreImagingProvider::class,
            ],
            "webPConverterOptions"  => [
                "type"    => "array",
                "default" => [],
            ],
        ]);

        return ["definitions" => $definitions, "options" => $options];
    }

}
