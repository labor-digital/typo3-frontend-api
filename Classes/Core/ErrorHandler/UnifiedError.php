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
 * Last modified: 2021.06.07 at 17:01
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Core\ErrorHandler;

use LaborDigital\T3ba\Core\Exception\NotImplementedException;
use League\Route\Http\Exception;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Error\Http\ForbiddenException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\Http\UnauthorizedException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;

class UnifiedError
{
    /**
     * Defines a list of TYPO3 exceptions and how we translate them internally to league http exceptions
     *
     * @var string[]
     */
    public static $throwableMap
        = [
            BadRequestException::class => Exception\BadRequestException::class,
            ForbiddenException::class => Exception\ForbiddenException::class,
            PageNotFoundException::class => NotFoundException::class,
            ServiceUnavailableException::class => NotFoundException::class,
            UnauthorizedException::class => Exception\UnauthorizedException::class,
            NotImplementedException::class => NotFoundException::class,
        ];
    
    /**
     * Defines a list of status codes that should be returned if the matched exception occurred.
     * This allows to configure HTTP status codes on non-http exceptions
     *
     * @var int[]
     */
    public static $throwableStatusMap
        = [
            ServiceUnavailableException::class => 503,
            NotImplementedException::class => 501,
        ];
    
    /**
     * The given throwable; as is
     *
     * @var Throwable
     */
    protected $rawError;
    
    /**
     * The error object that we resolved using our internal translation mechanism
     *
     * @var Throwable
     */
    protected $error;
    
    /**
     * The compiled message containing all nested messages
     *
     * @var string
     */
    protected $message;
    
    /**
     * Contains the call stack of the given error and all of its parents
     *
     * @var array
     */
    protected $stack = [];
    
    /**
     * A HTTP status code that was linked with this exception
     *
     * @var int
     */
    protected $statusCode = 500;
    
    /**
     * Additional metadata that was stored for this error
     *
     * @var array
     */
    protected $meta = [];
    
    public function __construct(Throwable $error, ?ServerRequestInterface $request)
    {
        $this->rawError = $error;
        $this->error = $this->translateError($error);
        $this->message = $this->generateExtendedErrorMessage($error, $request);
        $this->generateMeta($request);
        $this->convertThrowableToStack($error);
        $this->statusCode = method_exists($error, 'getStatusCode') ? $error->getStatusCode() : $this->statusCode;
    }
    
    /**
     * Returns the given throwable; as is
     *
     * @return \Throwable
     */
    public function getRawError(): Throwable
    {
        return $this->rawError;
    }
    
    /**
     * Returns the error object that we resolved using our internal translation mechanism
     *
     * @return \Throwable
     */
    public function getError(): Throwable
    {
        return $this->error;
    }
    
    /**
     * Returns the error message that was set for the contained error
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Returns the call stack of the given error and all of its parents
     *
     * @return array
     */
    public function getStack(): array
    {
        return $this->stack;
    }
    
    /**
     * Returns the HTTP status code that was linked with this exception
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * Returns additional metadata that was stored for this error
     *
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
    
    /**
     * Returns an array of "context" data that can be supplied to a logger method
     *
     * @return array
     */
    public function getLogContext(): array
    {
        return array_filter(
            [
                'meta' => $this->getMeta(),
                'stack' => array_map(function (array $entry) {
                    $entry = array_merge($entry, $entry['trace'][0]);
                    unset($entry['trace']);
                    
                    return $entry;
                }, $this->getStack()),
            ]
        );
    }
    
    /**
     * Helper to generate more speaking error messages
     *
     * @param   \Throwable                                     $error
     * @param   \Psr\Http\Message\ServerRequestInterface|null  $request
     *
     * @return string
     */
    protected function generateExtendedErrorMessage(Throwable $error, ?ServerRequestInterface $request): string
    {
        // Combine the error messages of all nested errors
        $e = $error;
        while ($e !== null) {
            $message[] = $e->getMessage();
            $e = $e->getPrevious();
        }
        $count = count($message);
        if ($count > 1) {
            foreach ($message as $k => $msg) {
                $message[$k] = '(' . ($k + 1) . '/' . $count . ') ' . $msg;
            }
        }
        $message = implode(' || ', $message);
        
        // Enhance the message by routing information
        if ($error instanceof Exception\HttpExceptionInterface && $request !== null) {
            $message .= ' (' . $request->getUri() . ')';
        }
        
        return $message;
    }
    
    /**
     * Unifies the multitude of exception options into a http exception if possible.
     *
     * @param   \Throwable  $error  The throwable to translate
     *
     * @return \Throwable
     */
    protected function translateError(Throwable $error): Throwable
    {
        // Translate immediate response exceptions
        if ($error instanceof ImmediateResponseException) {
            $this->statusCode = $error->getResponse()->getStatusCode();
            
            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            return new \League\Route\Http\Exception(
                $error->getResponse()->getStatusCode(),
                $error->getResponse()->getReasonPhrase(),
                $error,
                $error->getResponse()->getHeaders()
            );
        }
        
        // Translate convertible exceptions
        if ($error instanceof HttpConvertibleExceptionInterface) {
            return $error->getHttpException();
        }
        
        // Translate TYPO3 exceptions
        foreach (static::$throwableMap as $from => $to) {
            if ($error instanceof $from) {
                $code = $error->getCode();
                if (empty($code)) {
                    $code = static::$throwableStatusMap[$from] ?? 0;
                }
                $error = new $to($error->getMessage(), $error, $code);
                break;
            }
        }
        
        return $error;
    }
    
    /**
     * Currently only generates meta for the request object,
     * but could be extended to handle meta on the error or its "previous" errors as well.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface|null  $request
     */
    protected function generateMeta(?ServerRequestInterface $request): void
    {
        if ($request) {
            $this->meta['request'] = [
                'type' => 'http',
                'agent' => $request->getHeaderLine('User-Agent'),
                'method' => $request->getMethod(),
                'uri' => (string)$request->getUri(),
                'referer' => $request->getHeaderLine('Referer'),
            ];
        }
    }
    
    /**
     * Converts a chain of throwable objects into a php array and returns it.
     * Arguments will automatically be stripped to prevent log spam
     *
     * @param   \Throwable  $error
     */
    protected function convertThrowableToStack(Throwable $error): void
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        while ($error !== null) {
            $this->stack[] = [
                'title' => empty($error->getMessage()) ? $this->error->getMessage() : $error->getMessage(),
                'code' => $error->getCode(),
                'trace' => array_map(static function ($v) {
                    unset($v['args'], $v['type']);
                    if (isset($v['file'], $v['line'])) {
                        $v['file'] .= ' (' . $v['line'] . ')';
                        unset($v['line']);
                    }
                    
                    if (isset($v['class'])) {
                        $v['class'] = utf8_decode($v['class']);
                        if (str_contains($v['class'], 'anonymous')) {
                            $v['class'] = preg_replace('~[^a-zA-Z0-9@/\\\:.$]~si', ' ', $v['class']);
                        }
                    }
                    
                    return $v;
                }, $error->getTrace()),
            ];
            $error = $error->getPrevious();
        }
    }
}
