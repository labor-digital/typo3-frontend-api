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
 * Last modified: 2021.06.24 at 11:59
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Imaging;


class Request
{
    /**
     * Contains the request proxy handler if images should be proxied instead of redirected through 301
     *
     * @var \LaborDigital\T3fa\Core\Imaging\RequestProxyHandlerInterface
     */
    public $requestProxyHandler;
    
    /**
     * The name of the required file
     *
     * @var string
     */
    public $file;
    
    /**
     * The numeric uid of the requested file/file reference
     *
     * @var int
     */
    public $uid;
    
    /**
     * Cache busting hash of the file contents
     *
     * @var string
     */
    public $hash;
    
    /**
     * The image processing definition, required for the file
     *
     * @var string
     */
    public $definition;
    
    /**
     * The crop definition to apply for the file
     *
     * @var string|null
     */
    public $crop;
    
    /**
     * True if the image should be rendered in twice the size (retina displays)
     *
     * @var bool
     */
    public $isX2;
    
    /**
     * Contains the type of the request either "file" or "reference".
     * To determine if a file reference or fal file was required
     *
     * @var string
     */
    public $type;
    
    /**
     * True if the browser accepts webP images
     *
     * @var bool
     */
    public $acceptsWebP;
    
    /**
     * The absolute path to the redirect hash file
     *
     * @var string
     */
    public $redirectHashPath;
    
    /**
     * The absolute path to the file containing the redirect url for a file
     *
     * @var string
     */
    public $redirectInfoPath;
    
    /**
     * The base url to use when a relative url was provided
     *
     * @var string
     */
    public $baseUrl;
    
    /**
     * Returns true if the file should be looked up as file reference
     *
     * @return bool
     */
    public function isReference(): bool
    {
        return $this->type === 'reference';
    }
    
    /**
     * Returns true if the redirect hash file exists
     *
     * @return bool
     */
    public function hasRedirectHashFile(): bool
    {
        return is_file($this->redirectHashPath);
    }
    
    /**
     * Returns true if the redirect info file exists
     *
     * @return bool
     */
    public function hasRedirectInfoFile(): bool
    {
        return is_file($this->redirectInfoPath);
    }
    
    /**
     * Checks if both the redirect info and hash files exist and executes the
     * redirect to the correct target, before killing the process
     *
     * @todo can we return a response object here?
     */
    public function settleIfPossible(): void
    {
        if (! $this->hasRedirectInfoFile() || ! $this->hasRedirectHashFile()) {
            return;
        }
        
        $redirectInfoPath = $this->redirectInfoPath;
        if ($this->acceptsWebP && is_file($redirectInfoPath . '-webp')) {
            $redirectInfoPath .= '-webp';
        }
        
        $redirectTarget = @file_get_contents($redirectInfoPath);
        if (! $redirectTarget) {
            RequestFactory::error(404);
        }
        
        if (isset($this->requestProxyHandler)) {
            $this->requestProxyHandler->settle($redirectTarget, $this);
            exit();
        }
        
        if ($redirectTarget[0] === '/') {
            $redirectTarget = $this->baseUrl . $redirectTarget;
        }
        
        header('Location: ' . $redirectTarget, true, 301);
        exit();
    }
}