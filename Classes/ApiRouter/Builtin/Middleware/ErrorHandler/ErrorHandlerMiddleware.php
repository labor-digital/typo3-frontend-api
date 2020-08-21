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
 * Last modified: 2019.08.26 at 19:36
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\ErrorHandler;


use LaborDigital\Typo3FrontendApi\Whoops\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorHandlerMiddleware implements MiddlewareInterface {

	/**
	 * @var \LaborDigital\Typo3FrontendApi\Whoops\ErrorHandler
	 */
	protected $handler;

	/**
	 * Middleware constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\Whoops\ErrorHandler $handler
	 */
	public function __construct(ErrorHandler $handler) {
		$this->handler = $handler;
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		return $this->handler->handleErrorsIn(function () use ($request, $handler) {
			return $handler->handle($request);
		}, $request);
	}
}

