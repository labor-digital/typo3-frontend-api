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
 * Last modified: 2021.06.24 at 13:43
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Imaging;


use LaborDigital\T3ba\Core\Di\NoDiInterface;

class RequestFactory implements NoDiInterface
{
    /**
     * The request object if we need to boot TYPO3 to calculate the image variant first
     *
     * @var \LaborDigital\T3fa\Core\Imaging\Request
     */
    protected static $request;
    
    /**
     * Creates the imaging request from the server parameters
     *
     * @param   string  $requestPath
     *
     * @return \LaborDigital\T3fa\Core\Imaging\Request
     */
    public static function makeRequest(string $requestPath): Request
    {
        return static::$request = static::readAndValidateRequest($requestPath);
    }
    
    /**
     * Returns the request object or null if there is currently no request to handle
     *
     * @return \LaborDigital\T3fa\Core\Imaging\Request|null
     */
    public static function getRequest(): ?Request
    {
        return static::$request;
    }
    
    /**
     * Builds the request object based on the current browser request
     *
     * @param   string  $requestPath
     *
     * @return Request
     */
    protected static function readAndValidateRequest(string $requestPath): Request
    {
        $request = new Request();
        
        $file = basename(substr($requestPath, strlen(T3FA_IMAGING_ENDPOINT_PREFIX)));
        
        if (empty($file)) {
            static::error(400, 'Missing filename in the request path!');
        }
        $request->file = urldecode($file);
        
        // Extract the id and type from the requested file
        $fileParts = explode('.', $request->file);
        if (count($fileParts) !== 4) {
            static::error(400, 'Invalid file given! Three parts are expected!');
        }
        $request->hash = preg_replace('~[^a-fA-F0-9]~', '', $fileParts[1]);
        if (strlen($request->hash) !== 32) {
            static::error(400, 'Invalid hash given!');
        }
        $id = $fileParts[2];
        if (strlen($id) < 2) {
            static::error(400, 'Invalid file given! The id part is to short!');
        }
        $request->type = $id[0] === 'r' ? 'reference' : 'file';
        $request->uid = (int)substr($id, 1);
        if (empty($request->uid)) {
            static::error(404, 'Empty or invalid uid given!');
        }
        
        $request->definition = ! empty($_GET['definition']) && is_string($_GET['definition'])
            ? $_GET['definition'] : 'default';
        $request->crop = ! empty($_GET['crop']) && is_string($_GET['crop']) ? $_GET['crop'] : null;
        $request->isX2 = ! empty($_GET['x2']) && is_string($_GET['x2']) &&
                         in_array(strtolower($_GET['x2']), ['true', '1', 'yes', 'on'], true);
        $request->acceptsWebP = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'image/webp');
        
        $request->baseUrl = static::getBaseUrl();
        
        $idHash = md5($request->type . '-' . $request->uid);
        $redirectInfoPath = static::getRedirectInfoStoragePath();
        $redirectInfoPath .= '/' . $idHash[0] . '/' . $idHash[1] . '/' . $idHash[2] . '/';
        $redirectInfoPath .= $request->type . '-' . $request->uid . ($request->isX2 ? '-x2' : '') . '/';
        $redirectHashPath = $redirectInfoPath . $request->hash;
        $redirectInfoPath .= preg_replace('~[^a-zA-Z0-9\-_]~', '',
            $request->definition . rtrim('-' . $request->crop, '-'));
        $redirectInfoPath .= '-' . md5($request->definition . '.' . $request->crop);
        
        $request->redirectHashPath = $redirectHashPath;
        $request->redirectInfoPath = $redirectInfoPath;
        
        if (! empty(getenv('T3FA_IMAGING_USE_PROXY_INSTEAD_REDIRECT'))) {
            $request->requestProxyHandler = new RequestProxyHandler();
        }
        
        return $request;
    }
    
    public static function getRedirectInfoStoragePath(): string
    {
        $redirectInfoPath = BETTER_API_TYPO3_VAR_PATH . '/t3fa_imaging';
        if (getenv('T3FA_IMAGING_REDIRECT_INFO_STORAGE_PATH')) {
            $redirectInfoPath = rtrim((string)getenv('T3FA_IMAGING_REDIRECT_INFO_STORAGE_PATH'), '\\/');
        }
        
        return $redirectInfoPath;
    }
    
    /**
     * Helper to handle an error while processing the imaging request
     *
     * @param   int          $code     The http status code
     * @param   string|null  $msg      Optional message for the user to see
     * @param   string|null  $httpMsg  Optional http message otherwise inferred from the status code
     */
    public static function error(int $code, ?string $msg = null, ?string $httpMsg = null): void
    {
        $httpMsgList = [404 => 'Not Found', 500 => 'Internal Server Error', 400 => 'Bad Request'];
        if (empty($httpMsg)) {
            $httpMsg = $httpMsgList[$code] ?? 'Internal Server Error';
        }
        header('HTTP/1.0 ' . $code . ' ' . $httpMsg);
        http_response_code($code);
        die(empty($msg) ? $httpMsg : $msg);
    }
    
    /**
     * Finds the base url based on the current request
     *
     * @return string
     */
    protected static function getBaseUrl(): string
    {
        if (getenv('T3FA_IMAGING_BASE_URL')) {
            return rtrim((string)getenv('T3FA_IMAGING_BASE_URL'), '\\/');
        }
        
        $scheme = static::fetchScheme($_SERVER);
        [$host, $port] = static::fetchHostname($_SERVER);
        
        if (! empty($port) && $port !== 443 && $port !== 80) {
            $host .= ':' . $port;
        }
        
        return $scheme . '://' . $host;
    }
    
    /**
     * Returns the environment scheme.
     */
    protected static function fetchScheme(array $server): string
    {
        $server += ['HTTPS' => ''];
        $res = filter_var($server['HTTPS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        
        return false !== $res ? 'https' : 'http';
    }
    
    /**
     * Returns the environment host.
     *
     * @return array {0:?string, 1:?string}
     */
    protected static function fetchHostname(array $server): ?array
    {
        $server += ['SERVER_PORT' => null];
        if (null !== $server['SERVER_PORT']) {
            $server['SERVER_PORT'] = (int)$server['SERVER_PORT'];
        }
        
        if (isset($server['HTTP_HOST'])) {
            preg_match(',^(?<host>(\[.*]|[^:])*)(:(?<port>[^/?#]*))?$,x', $server['HTTP_HOST'], $matches);
            
            return [
                $matches['host'],
                isset($matches['port']) ? (int)$matches['port'] : $server['SERVER_PORT'],
            ];
        }
        
        if (! isset($server['SERVER_ADDR'])) {
            return ['localhost', ''];
        }
        
        if (false === filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $server['SERVER_ADDR'] = '[' . $server['SERVER_ADDR'] . ']';
        }
        
        return [$server['SERVER_ADDR'], $server['SERVER_PORT']];
    }
}