<?php
declare(strict_types=1);
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
 * Last modified: 2019.09.17 at 14:46
 */

namespace LaborDigital\Typo3FrontendApi\TypoMiddleware;


use GuzzleHttp\Psr7\ServerRequest;
use LaborDigital\Typo3FrontendApi\ApiRouter\ApiRouter;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use LaborDigital\Typo3FrontendApi\Site\SiteNotConfiguredException;
use Neunerlei\FileSystem\Fs;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function GuzzleHttp\Psr7\stream_for;

class ApiMiddlewareFork implements MiddlewareInterface
{
    use ResponseFactoryTrait;
    use FrontendApiContextAwareTrait;

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->FrontendApiContext()->getInstanceOf(ApiRouter::class);

        // Check if we handle this request using the api endpoint
        if ($router->canHandlePath($request->getUri()->getPath())) {
            // Make sure we get all headers
            $fallbackRequest = ServerRequest::fromGlobals();
            foreach ($fallbackRequest->getHeaders() as $k => $v) {
                if (! $request->hasHeader($k)) {
                    $request = $request->withHeader($k, $v);
                }
            }

            // Let the router handle the request
            return $router->handle($request);
        }

        // Check if we have a static template for this site
        try {
            $staticTemplate = $this->FrontendApiContext()->getCurrentSiteConfig()->staticTemplate;
            if (! empty($staticTemplate)) {
                return $this->handleWithStaticTemplate($staticTemplate);
            }
        } catch (SiteNotConfiguredException $e) {
            // Ignore this exception -> App is running in hybrid mode
        }

        // Handle with default method
        return $handler->handle($request);
    }

    /**
     * Internal request handler which returns the registered, static template back to the browser
     *
     * @param   string  $staticTemplate
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleWithStaticTemplate(string $staticTemplate): ResponseInterface
    {
        $templateExists = ! file_exists($staticTemplate);
        if ($templateExists) {
            $content = "<html lang=\"en\"><head><title>Template not found!</title></head><body>The configured, static template was not found!</body></html>";
        } else {
            $content = Fs::readFile($staticTemplate);
        }

        return $this->getResponse($templateExists ? 200 : 500)->withBody(stream_for($content))->withAddedHeader("Content-Type", "text/html");
    }
}
