<?php
/*
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
 * Last modified: 2020.09.28 at 16:59
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\FrontendSimulation;


use League\Route\Http\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DokTypeValidationMiddleware implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected $allowedDokTypes;

    /**
     * DokTypeValidationMiddleware constructor.
     *
     * @param   array  $allowedDokTypes
     */
    public function __construct(array $allowedDokTypes)
    {
        $this->allowedDokTypes = $allowedDokTypes;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Update global request, one last time
        $GLOBALS['TYPO3_REQUEST']          = $request;
        $GLOBALS['TYPO3_REQUEST_FALLBACK'] = $request;

        // Check if this is an allowed page type
        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
        $tsfe    = $GLOBALS['TSFE'];
        $dokType = (int)$tsfe->page['doktype'];
        if (! in_array($dokType, $this->allowedDokTypes, true)) {
            throw new BadRequestException('The dokType of the given page is not allowed!');
        }

        return $handler->handle($request);
    }

}
