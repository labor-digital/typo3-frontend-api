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
 * Last modified: 2020.04.01 at 11:48
 */

namespace LaborDigital\Typo3FrontendApi\Imaging;

use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use TYPO3\CMS\Core\Core\ApplicationInterface;

class ImagingApplication implements ApplicationInterface {
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\Imaging\ImagingContext
	 */
	protected $context;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
	 */
	protected $typoContext;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\Imaging\ImagingProcessorService
	 */
	protected $imagingService;
	
	/**
	 * ImagingApplication constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\Imaging\ImagingContext          $context
	 * @param \LaborDigital\Typo3BetterApi\TypoContext\TypoContext           $typoContext
	 * @param \LaborDigital\Typo3FrontendApi\Imaging\ImagingProcessorService $imagingService
	 */
	public function __construct(ImagingContext $context, TypoContext $typoContext, ImagingProcessorService $imagingService) {
		$this->context = $context;
		$this->typoContext = $typoContext;
		$this->imagingService = $imagingService;
	}
	
	/**
	 * @inheritDoc
	 */
	public function run(callable $execute = NULL) {
		try {
			$this->imagingService->process($this->context);
		} catch (\Throwable $e) {
			if ($e->getCode() === 400)
				$this->handleError(400, "Bad Request", $e->getMessage());
			if ($e->getCode() === 404)
				$this->handleError(404, "Not Found", $e->getMessage());
			if ($this->typoContext->getEnvAspect()->isDev()) throw $e;
			$this->handleError(500, "Internal Server Error");
		}
	}
	
	/**
	 * Handles an error and kills the script
	 *
	 * @param int         $code
	 * @param string      $httpString
	 * @param string|null $message
	 */
	protected function handleError(int $code, string $httpString, ?string $message = NULL): void {
		header("HTTP/1.0 $code $httpString");
		http_response_code($code);
		die(empty($message) ? $httpString : $message);
	}
	
}