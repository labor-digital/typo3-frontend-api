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
 * Last modified: 2019.08.07 at 15:55
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Strategy;

use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\CacheControllingStrategyTrait;
use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use League\Route\Strategy\JsonStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ExtendedJsonStrategy extends JsonStrategy {
	use CacheControllingStrategyTrait;
	
	/**
	 * @inheritDoc
	 */
	public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface {
		$response = parent::invokeRouteCallable($route, $request);
		return $this->addInternalNoCacheHeaderIfRequired($route, $response);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getExceptionHandler(): MiddlewareInterface {
		return $this->buildNullThrowableHandler();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getThrowableHandler(): MiddlewareInterface {
		return $this->buildNullThrowableHandler();
	}
	
	/**
	 * Let exceptions fall through to the error handler
	 * @return \Psr\Http\Server\MiddlewareInterface
	 */
	protected function buildNullThrowableHandler(): MiddlewareInterface {
		return new class implements MiddlewareInterface {
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
				return $handler->handle($request);
			}
		};
	}
	
	/**
	 * @inheritDoc
	 */
	protected function buildJsonResponseMiddleware(HttpException $exception): MiddlewareInterface {
		// As we have our own error handler we don't need this...
		return new class($exception) implements MiddlewareInterface {
			
			/**
			 * @var \Throwable
			 */
			protected $exception;
			
			/**
			 * Exception middleware constructor.
			 *
			 * @param \Throwable $exception
			 */
			public function __construct(Throwable $exception) {
				$this->exception = $exception;
			}
			
			/**
			 * @inheritDoc
			 * @throws \Exception
			 */
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
				throw $this->exception;
			}
		};
	}
}