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
 * Last modified: 2020.01.20 at 16:45
 */

namespace LaborDigital\Typo3FrontendApi\ExtConfig;


use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractChildExtConfigOption;

class FrontendApiToolOption extends AbstractChildExtConfigOption {
	
	
	/**
	 * Registers a route in the frontend api that can be accessed on the /api/up endpoint.
	 * It just returns "OK" and a state of 200 if the system is running as desired.
	 *
	 * @param bool $enable
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiToolOption
	 */
	public function configureUpRoute(bool $enable = TRUE): FrontendApiToolOption {
		return $this->addToCachedValueConfig("config", $enable, "up");
	}
	
	/**
	 * Registers a route in the frontend api to execute the TYPO3 scheduler via HTTP request.
	 * The endpoint is accessible on /api/scheduler/run to run the whole scheduler task list
	 * If you provide the id of a given task like /api/scheduler/run/1 for task ID 1 you can also execute a single task.
	 * While you are running in a Dev environment and execute a single task it will always be forced to run, ignoring the cronjob configuration;
	 * can be used to debug your scheduler tasks locally.
	 *
	 * Currently it is not possible to force a scheduler task to run in the production environment
	 *
	 * @param string|array $token   Defines either a single or
	 *                              multiple tokens that act as "password" to access the scheduler endpoint.
	 *                              The token can either be received using the Authentication Bearer header
	 *                              or via query parameter "token", when it is enabled by setting "allowTokenInQuery" to true
	 * @param array        $options The options to configure the scheduler execution
	 *                              - enabled bool (TRUE): True by default, enables the endpoint,
	 *                              setting this to false disables it after it was previously enabled.
	 *                              - maxExecutionType int (60*10): The number in seconds the php script
	 *                              can run before it is forcefully killed by the server.
	 *                              - allowTokenInQuery bool (FALSE): If set to true the token may be passed by query
	 *                              parameter instead of a HTTP header. This is TRUE by default if you are running
	 *                              in a dev environment.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiToolOption
	 */
	public function configureSchedulerRoute($token, array $options = []): FrontendApiToolOption {
		$options["token"] = $token;
		return $this->addToCachedValueConfig("config", $options, "scheduler");
	}
	
	/**
	 * Configures the advanced imaging endpoint. It provides you with support for on-the-fly image
	 * processing and serves images as webp where possible.
	 *
	 * To configure the endpoint you have to provide a list of processing definitions for the
	 * size of the image. Each definition has a unique key. The key can be used in the imaging.php endpoint
	 * to tell the server which image size you want to have served. You can also set a default cropping definition
	 * which can be overwritten by the request to imaging.php, too.
	 *
	 * The definition of the sets is done as array with the $key as the unique key of the definition
	 * and the $value as array containing an imaging definition like you would when you use getResizedFile()
	 * on the FalFileService!
	 *
	 * NOTE: "default" is a special key. It applies to ALL images that don't have a specific definition key given!
	 *
	 * @param array $definitions A list of key => value pairs to define the imaging definitions
	 *                           Each value array can contain the following keys:
	 *                           - width int|string: see *1
	 *                           - height int|string: see *1
	 *                           - minWidth int The minimal width of the image in pixels
	 *                           - minHeight int The minimal height of the image in pixels
	 *                           - maxWidth int The maximal width of the image in pixels
	 *                           - maxHeight int The maximal height of the image in pixels
	 *                           - crop bool|string|array: True if the image should be cropped instead of stretched
	 *                           Can also be the name of a cropVariant that should be rendered
	 *                           Can be an array with (x,y,width,height) keys to provide a custom crop mask
	 *                           Can be overwritten using the "crop" GET parameter on the endpoint
	 *                           - params string: Additional command line parameters for imagick
	 *                           see: https://imagemagick.org/script/command-line-options.php
	 * @param array $options     Additional options for the imaging endpoint
	 *                           - redirectDirectoryPath string: defines the directory
	 *                           where the redirect information is stored (not the real image files!).
	 *                           DEFAULT: The default path to the var directory based on your TYPO3 config /imaging
	 *                           - endpointDirectoryPath string: The directory where the the imaging.php entry point
	 *                           should be compiled. The directory has to be writable by the webserver!
	 *                           DEFAULT: The default fileadmin directory inside your public folder
	 *                           - imagingProvider string: By default the processing is done by the TYPO3 core
	 *                           if you want to use another provider like s3 as a backend you can create your own
	 *                           imaging provider. The given value is the class name of your provider.
	 *                           The class has to implement the ImagingProviderInterface!
	 *                           DEFAULT: LaborDigital\Typo3FrontendApi\Imaging\Provider\CoreImagingProvider
	 *                           - webPConverterOptions array: Optional, additional options to be passed to the
	 *                           webP converter implementation (rosell-dk/webp-convert). See the link below for possible options
	 *
	 * *1: A numeric value, can also be a simple calculation. For further details take a look at imageResource.width:
	 * https://docs.typo3.org/m/typo3/reference-typoscript/8.7/en-us/Functions/Imgresource/Index.html
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiToolOption
	 * @see \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService::getResizedImage()
	 * @see https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/options.md
	 */
	public function configureImaging(array $definitions = [], array $options = []): FrontendApiToolOption {
		return $this->addToCachedValueConfig("config", [$definitions, $options], "imaging");
	}
	
	/**
	 * Internal helper to fill the main config repository' config array with the local configuration
	 *
	 * @param array $config
	 */
	public function __buildConfig(array &$config): void {
		$config["tool"] = $this->getCachedValueOrRun("config", FrontendApiToolConfigGenerator::class);
	}
}