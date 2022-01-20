<?php
/*
 * Copyright 2022 LABOR.digital
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
 * Last modified: 2022.01.20 at 11:57
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Imaging;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class RequestProxyHandler implements RequestProxyHandlerInterface
{
    protected const PROXY_HEADERS
        = [
            'Date',
            'Expires',
            'Cache-Control',
            'Content-Length',
            'Last-Modified',
            'Age',
            'Content-Type',
            'ETag',
        ];
    
    /**
     * @inheritDoc
     */
    public function settle(string $redirectTarget, Request $request): void
    {
        $docRoot = $this->fetchDocumentRoot();
        if ($redirectTarget[0] === '/') {
            if ($docRoot && file_exists($docRoot . DIRECTORY_SEPARATOR . $redirectTarget)) {
                $this->dumpLocalFile($docRoot . DIRECTORY_SEPARATOR . $redirectTarget);
            }
            $redirectTarget = $request->baseUrl . $redirectTarget;
        }
        
        $this->streamProxyImage($redirectTarget);
    }
    
    /**
     * Used to output a local image as response to the request.
     *
     * @param   string  $filename
     *
     * @return void
     */
    protected function dumpLocalFile(string $filename): void
    {
        $size = getimagesize($filename);
        $fp = fopen($filename, 'rb');
        
        if ($size && $fp) {
            header('Content-Type: ' . $size['mime']);
            header('Content-Length: ' . filesize($filename));
            
            fpassthru($fp);
            fclose($fp);
        } else {
            RequestFactory::error(404);
        }
        
        exit();
    }
    
    /**
     * Streams the image using guzzle as a proxy from the source url
     *
     * @param   string  $url
     *
     * @return void
     */
    protected function streamProxyImage(string $url): void
    {
        try {
            $response = (new Client())->request('GET', $url, [
                'timeout' => max((int)getenv('T3FA_IMAGING_PROXY_TIMEOUT'), 30),
                'stream' => true,
            ]);
            
            if ($response->getStatusCode() !== 200) {
                RequestFactory::error($response->getStatusCode(), null, $response->getReasonPhrase());
            }
            
            foreach (static::PROXY_HEADERS as $headerName) {
                if (isset($response->getHeader($headerName)[0])) {
                    header($headerName . ': ' . $response->getHeader($headerName)[0]);
                }
            }
            
            while (! $response->getBody()->eof()) {
                echo $response->getBody()->read(1024);
                flush();
            }
            
            exit();
        } catch (ClientException $e) {
            if ($e->getResponse()) {
                $response = $e->getResponse();
                
                if ($response->getStatusCode() === 401) {
                    RequestFactory::error(407, null, 'Proxy Authentication Required');
                }
                
                RequestFactory::error($e->getResponse()->getStatusCode(), null, $e->getResponse()->getReasonPhrase());
            }
            
            RequestFactory::error(502, null, 'Bad Gateway');
        } catch (\Throwable $e) {
            RequestFactory::error(503);
        }
    }
    
    /**
     * Resolves the document root to resolve local images in
     *
     * @return string|null
     */
    protected function fetchDocumentRoot(): ?string
    {
        if (getenv('T3FA_IMAGING_DOCUMENT_ROOT')) {
            return rtrim((string)getenv('T3FA_IMAGING_DOCUMENT_ROOT'), '\\/');
        }
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            return dirname($_SERVER['SCRIPT_FILENAME']);
        }
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            return rtrim($_SERVER['DOCUMENT_ROOT'], '\\/');
        }
        
        return null;
    }
    
}