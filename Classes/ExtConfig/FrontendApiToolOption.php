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
use Neunerlei\Options\Options;

class FrontendApiToolOption extends AbstractChildExtConfigOption {
	
	/**
	 * Holds the raw configuration while we collect the options
	 * @var array
	 */
	protected $config = [
		"up"        => [
			"enabled" => FALSE,
		],
		"scheduler" => [
			"enabled" => FALSE,
		],
	];
	
	
	/**
	 * Registers a route in the frontend api that can be accessed on the /api/up endpoint.
	 * It just returns "OK" and a state of 200 if the system is running as desired.
	 *
	 * @param bool $enable
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiToolOption
	 */
	public function configureUpRoute(bool $enable = TRUE): FrontendApiToolOption {
		$this->config["up"]["enabled"] = $enable;
		return $this;
	}
	
	/**
	 * Registers a route in the frontend api to execute the TYPO3 scheduler via HTTP request.
	 * Currently it is only supported to trigger the whole task-list and not single tasks.
	 * The endpoint is accessible on /api/scheduler/run
	 *
	 * @param array $options The options to configure the scheduler execution
	 *                       - enabled bool (TRUE): True by default, enables the endpoint,
	 *                       setting this to false disables it after it was previously enabled.
	 *                       - maxExecutionType int (60*10): The number in seconds the php script
	 *                       can run before it is forcefully killed by the server.
	 *                       - token string|array: REQUIRED argument that defines either a single or
	 *                       multiple tokens that act as "password" to access the scheduler endpoint.
	 *                       The token can either be received using the Authentication Bearer header
	 *                       or via query parameter "token", when it is enabled by setting "allowTokenInQuery" to true.
	 *                       - allowTokenInQuery bool (FALSE): If set to true the token may be passed by query
	 *                       parameter instead of a HTTP header. This is TRUE by default if you are running
	 *                       in a dev environment.
	 *
	 * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiToolOption
	 */
	public function configureSchedulerRoute(array $options = []): FrontendApiToolOption {
		$this->config["scheduler"] = Options::make($options, [
			"enabled"           => [
				"type"    => "bool",
				"default" => TRUE,
			],
			"maxExecutionType"  => [
				"type"    => "int",
				"default" => 60 * 10,
			],
			"token"             => [
				"type"   => ["string", "array"],
				"filter" => function ($v) {
					if (is_string($v)) return [$v];
					return array_values($v);
				},
			],
			"allowTokenInQuery" => [
				"type"    => "bool",
				"default" => $this->context->TypoContext->getEnvAspect()->isDev(),
			],
		]);
		return $this;
	}
	
	/**
	 * Internal helper to fill the main config repository' config array with the local configuration
	 *
	 * @param array $config
	 */
	public function __buildConfig(array &$config): void {
		$config["tool"] = $this->config;
	}
}