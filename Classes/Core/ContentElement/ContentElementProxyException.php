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
 * Last modified: 2021.07.13 at 13:00
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement;


use Throwable;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Response;

/**
 * Class ContentElementProxyException
 *
 * This construct is used to proxy HTTP exceptions inside a content element error boundary through
 * the content element exception handler of the TYPO3 core.
 *
 * WARNING: This is a highly experimental implementation detail. Please don't rely on it.
 *
 * @package LaborDigital\T3fa\Core\ContentElement
 * @internal
 */
class ContentElementProxyException extends ImmediateResponseException
{
    /**
     * True if this exception can be used false if no handler for it has been registered
     *
     * @var bool
     */
    protected static $enabled = false;
    
    /**
     * @var \Throwable
     */
    protected $throwable;
    
    public function __construct(Throwable $throwable)
    {
        if (! static::$enabled) {
            throw new ContentElementException('The proxy exception can\'t be used, because it was not enabled!');
        }
        
        parent::__construct(new Response('php://temp', 500), 500);
        $this->throwable = $throwable;
    }
    
    /**
     * Returns the actually thrown exception
     *
     * @return \Throwable
     */
    public function getProxiedException(): Throwable
    {
        return $this->throwable;
    }
    
    /**
     * Enables the exception to be used
     *
     * @param   bool  $state
     */
    public static function enable(bool $state = true): void
    {
        static::$enabled = $state;
    }
    
    /**
     * Returns true if the exception is enabled, and can be handled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return static::$enabled;
    }
}