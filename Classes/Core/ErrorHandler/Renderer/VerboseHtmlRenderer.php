<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.05.25 at 13:59
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ErrorHandler\Renderer;


use LaborDigital\T3ba\Core\ErrorHandler\DebugExceptionHandler;
use LaborDigital\T3fa\Core\ErrorHandler\UnifiedError;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class VerboseHtmlRenderer
 *
 * We use the actual debug exception handler to render the error for HTML
 *
 * @package LaborDigital\T3fa\Core\ErrorHandler\Handler
 */
class VerboseHtmlRenderer extends DebugExceptionHandler implements VerboseRendererInterface
{
    /**
     * Disables the exception handler registration
     *
     * @inheritDoc
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
        if (empty(static::$defaultExceptionHandler)) {
            if (! empty(DebugExceptionHandler::$defaultExceptionHandler)) {
                static::$defaultExceptionHandler = DebugExceptionHandler::$defaultExceptionHandler;
            } else {
                static::$defaultExceptionHandler = \TYPO3\CMS\Core\Error\DebugExceptionHandler::class;
            }
        }
        
        $this->defaultExceptionHandlerInstance = GeneralUtility::makeInstance(static::$defaultExceptionHandler);
        
        // Disable the child exception handler's handling
        restore_exception_handler();
    }
    
    /**
     * @inheritDoc
     */
    public function render(UnifiedError $error): string
    {
        ob_start();
        
        $rawError = $error->getRawError();
        if ($rawError instanceof ImmediateResponseException) {
            return (string)$rawError->getResponse()->getBody();
        }
        
        $this->defaultExceptionHandlerInstance->echoExceptionWeb($rawError);
        
        return ob_get_clean();
    }
}