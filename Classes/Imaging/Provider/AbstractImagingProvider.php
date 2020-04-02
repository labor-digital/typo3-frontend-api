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
 * Last modified: 2020.04.01 at 20:17
 */

namespace LaborDigital\Typo3FrontendApi\Imaging\Provider;


/**
 * Class AbstractImagingProvider
 *
 * basic implementation for your imaging provider classes
 *
 * @package LaborDigital\Typo3FrontendApi\Imaging\Provider
 */
abstract class AbstractImagingProvider implements ImagingProviderInterface {
	/**
	 * @var string
	 */
	protected $defaultRedirect;
	
	/**
	 * @var string|null;
	 */
	protected $webPRedirect;
	
	/**
	 * @inheritDoc
	 */
	public function getDefaultRedirect(): string {
		return $this->defaultRedirect;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getWebPRedirect(): ?string {
		return $this->webPRedirect;
	}
	
}