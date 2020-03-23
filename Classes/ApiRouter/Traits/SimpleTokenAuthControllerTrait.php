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
 * Last modified: 2020.01.20 at 15:12
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Traits;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use League\Route\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;

trait SimpleTokenAuthControllerTrait {
	
	/**
	 * To keep this trait agnostic to your token provider your controller has to resolve
	 * the tokens and provide them to this trait by implementing this method.
	 * The trait expects getTokens() to return an array of valid token strings.
	 *
	 * @return array
	 */
	abstract protected function getTokens(): array;
	
	/**
	 * Validates if the given request has a token that is in the list of valid tokens.
	 * If the request contains a valid token this method returns true, false if not.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request              The request to validate
	 * @param bool                                     $allowQuery           By default only the Bearer header is
	 *                                                                       checked for the token, if you set this to
	 *                                                                       TRUE the method will also look for a
	 *                                                                       "token" argument in the requests query
	 *                                                                       parameters.
	 * @param bool                                     $alwaysAllowInDevMode Set this to FALSE if you want to validate
	 *                                                                       the token in dev mode, too.
	 *
	 * @return bool
	 */
	protected function validateToken(ServerRequestInterface $request, bool $allowQuery = FALSE, bool $alwaysAllowInDevMode = TRUE): bool {
		// Always allow if we are running in dev mode
		if ($alwaysAllowInDevMode && TypoContainer::getInstance()->get(TypoContext::class)->getEnvAspect()->isDev())
			return TRUE;
		
		// Try to find the token using the header
		$token = NULL;
		if ($request->hasHeader("Authorization")) {
			$header = $request->getHeaderLine("Authorization");
			$token = trim(substr(trim($header), 6));
		}
		
		// Try to find the token in the query parameters
		if (empty($token) && $allowQuery) {
			$args = $request->getQueryParams();
			if (!empty($args["token"])) $token = trim($args["token"]);
		}
		
		// Try to fall back to apache request headers array
		// @see https://stackoverflow.com/a/39137869/11811755
		if (empty($token) && function_exists("apache_request_headers")) {
			/** @noinspection PhpComposerExtensionStubsInspection */
			$headers = apache_request_headers();
			if (!empty($headers["Authorization"])) $token = trim(substr(trim($headers["Authorization"]), 6));
		}
		
		// Skip if there was no token
		if (empty($token)) return FALSE;
		
		// Validate the token with the list
		$tokens = $this->getTokens();
		return in_array($token, $tokens);
	}
	
	/**
	 * Similar to validateToken() but throws a Unauthorized exception if the request could not be validated
	 *
	 * @param ServerRequestInterface $request              The request to validate
	 * @param bool                   $allowQuery           By default only the Bearer header is checked for the token,
	 *                                                     if you set this to TRUE the method will also look for a
	 *                                                     "token" argument in the requests query parameters.
	 * @param bool                   $alwaysAllowInDevMode Set this to FALSE if you want to validate the token in dev
	 *                                                     mode, too.
	 *
	 * @throws \League\Route\Http\Exception\UnauthorizedException
	 */
	protected function validateTokenOrDie(ServerRequestInterface $request, bool $allowQuery = FALSE, bool $alwaysAllowInDevMode = TRUE): void {
		if (!$this->validateToken($request, $allowQuery, $alwaysAllowInDevMode))
			throw new UnauthorizedException();
	}
}