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
 * Last modified: 2021.07.14 at 21:58
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing\Util;


use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Event\Routing\BeforeRequestManglingEvent;
use LaborDigital\T3fa\Event\Routing\TypoRequestFilterEvent;
use Neunerlei\Inflection\Inflector;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Throwable;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RequestRewriter
{
    public const REQUEST_SLUG_HEADER = 'x-t3fa-slug';
    public const REQUEST_SLUG_QUERY_KEY = 'slug';
    public const REQUEST_SITE_HEADER = 'x-t3fa-site-identifier';
    public const REQUEST_SITE_QUERY_KEY = 'siteIdentifier';
    public const REQUEST_LANG_HEADER = 'x-t3fa-language';
    public const REQUEST_LANG_QUERY_KEY = 'L';
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $context;
    
    /**
     * @var \TYPO3\CMS\Core\Http\ServerRequestFactory
     */
    protected $requestFactory;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        TypoContext $context,
        ServerRequestFactory $requestFactory,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->context = $context;
        $this->requestFactory = $requestFactory;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Converts the known list of configuration headers into query parameters
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function rewriteHeadersToQueryParams(ServerRequestInterface $request): ServerRequestInterface
    {
        $request = $this->rewriteHeaderToQueryParam(
            $request, static::REQUEST_SLUG_HEADER, static::REQUEST_SLUG_QUERY_KEY);
        
        $request = $this->rewriteHeaderToQueryParam(
            $request, static::REQUEST_SITE_HEADER, static::REQUEST_SITE_QUERY_KEY);
        
        $request = $this->rewriteHeaderToQueryParam(
            $request, static::REQUEST_LANG_HEADER, static::REQUEST_LANG_QUERY_KEY);
        
        if (! empty($request->getQueryParams())) {
            return $request->withUri(
                $request->getUri()->withQuery(
                    http_build_query($request->getQueryParams())
                )
            );
        }
        
        return $request;
    }
    
    /**
     * Rewrites the given request for the TYPO3 core. Sadly this includes changes on $_GET and $_SERVER,
     * which have to be reverted after the lifecycle ends. Therefore the process is wrapped by this method.
     *
     * @param   \Closure                                  $callback  The callback receives the modified
     *                                                               Server request to provide to TYPO3
     * @param   \Psr\Http\Message\ServerRequestInterface  $request   The original request that should be rewritten
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function runWithTypoEnvironment(\Closure $callback, ServerRequestInterface $request): ResponseInterface
    {
        $getBackup = $_GET;
        $getVarsBackup = $GLOBALS['HTTP_GET_VARS'];
        $serverBackup = $_SERVER;
        
        try {
            $request = $this->eventDispatcher->dispatch(
                new BeforeRequestManglingEvent($request)
            )->getRequest();
            
            $GLOBALS['HTTP_GET_VARS'] = null;
            $_GET = $this->rewriteQueryParams($request->getQueryParams());
            
            $typoUri = $this->rewriteHost($this->rewriteSlug($request), $request);
            if (! empty($_GET)) {
                $typoUri = $typoUri->withQuery(http_build_query($_GET));
            }
            
            $_SERVER = $this->rewriteServerParams($typoUri);
            
            GeneralUtility::flushInternalRuntimeCaches();
            
            $typoRequest = $this->requestFactory->createServerRequest($request->getMethod(), $typoUri, $_SERVER);
            $typoRequest = $typoRequest->withQueryParams($_GET);
            $typoRequest = $typoRequest->withParsedBody($request->getParsedBody());
            $typoRequest = $typoRequest->withCookieParams($request->getCookieParams());
            $typoRequest = $typoRequest->withAttribute('originalRequest', $request);
            
            foreach ($request->getAttributes() as $key => $attribute) {
                $typoRequest = $typoRequest->withAttribute($key, $attribute);
            }
            
            $typoRequest = $typoRequest->withAttribute('normalizedParams', NormalizedParams::createFromRequest($typoRequest));
            $typoRequest = $this->eventDispatcher->dispatch(
                new TypoRequestFilterEvent($typoRequest, $request)
            )->getTypoRequest();
            
            return $callback($typoRequest);
            
        } finally {
            $_GET = $getBackup;
            $GLOBALS['HTTP_GET_VARS'] = $getVarsBackup;
            $_SERVER = $serverBackup;
        }
    }
    
    /**
     * Strips out all query parameters that don't start with tx_ and therefore are not part of the extbase naming schema
     *
     * @param   array  $queryParams
     *
     * @return array
     */
    protected function rewriteQueryParams(array $queryParams): array
    {
        $inheritedQueryParams = [];
        
        // @todo this should be configurable via an option
        $allowedParams = ['cHash', 'id', static::REQUEST_LANG_QUERY_KEY, 'no_cache'];
        
        foreach ($queryParams as $k => $v) {
            if (str_starts_with((string)$k, 'tx_') || in_array($k, $allowedParams, true)) {
                $inheritedQueryParams[$k] = $v;
            }
        }
        
        return $inheritedQueryParams;
    }
    
    /**
     * Rewrites the given request object by checking if a language code was passed either as a header or as query parameter
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function rewriteLanguageAttribute(ServerRequestInterface $request): ServerRequestInterface
    {
        $language = $request->getQueryParams()[static::REQUEST_LANG_QUERY_KEY] ?? null;
        if ($language !== null) {
            $lang = $this->resolveLanguage($language, $request->getAttribute('site'));
            if ($lang !== null) {
                return $request->withAttribute('language', $lang);
            }
        }
        
        return $request;
    }
    
    /**
     * Rewrites the given "slug" to be part of the request we pass to TYPO3
     *
     * @param   \Psr\Http\Message\UriInterface            $uri
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\UriInterface
     */
    protected function rewriteSlug(ServerRequestInterface $request): UriInterface
    {
        $slug = $request->getQueryParams()[static::REQUEST_SLUG_QUERY_KEY] ?? '/';
        
        try {
            $uri = $request->getUri();
            
            return new Uri($uri->getScheme() . '://' . $uri->getHost() . '/' . ltrim($slug, '/'));
        } catch (Throwable $e) {
            throw new BadRequestException('The required slug: "' . $slug . '" seems to be invalid');
        }
    }
    
    /**
     * Rewrites the uris host based on either the given site identifier or site host name
     *
     * @param   \Psr\Http\Message\UriInterface            $uri
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\UriInterface
     * @throws \TYPO3\CMS\Core\Error\Http\BadRequestException
     */
    protected function rewriteHost(UriInterface $uri, ServerRequestInterface $request): UriInterface
    {
        $siteIdentifier = $request->getQueryParams()[static::REQUEST_SITE_QUERY_KEY] ?? null;
        if ($siteIdentifier !== null) {
            if (! $this->context->site()->has($siteIdentifier)) {
                throw new BadRequestException('The required site: "' . $siteIdentifier . '" does not exist');
            }
            
            $hostUri = $this->context->site()->get($siteIdentifier)->getBase();
        }
        
        if (! isset($hostUri)) {
            return $uri;
        }
        
        if (! empty($hostUri->getScheme())) {
            $uri = $uri->withScheme($hostUri->getScheme());
        }
        
        if (! empty($hostUri->getHost())) {
            $uri = $uri->withHost($hostUri->getHost());
        }
        
        return $uri;
    }
    
    /**
     * Rewrites the _SERVER params to match the given uri and returns them as array
     *
     * @param   \Psr\Http\Message\UriInterface  $uri
     *
     * @return array
     */
    protected function rewriteServerParams(UriInterface $uri): array
    {
        $serverParams = $_SERVER;
        
        $serverParams['QUERY_STRING'] = $uri->getQuery();
        $serverParams['HTTP_HOST'] = $uri->getHost();
        $serverParams['REQUEST_URI'] = rtrim($uri->getPath() . '?' . $uri->getQuery(), '?');
        if (isset($serverParams['REDIRECT_URL'])) {
            $serverParams['REDIRECT_URL'] = $uri->getPath();
        }
        if (isset($serverParams['REDIRECT_QUERY_STRING'])) {
            $serverParams['REDIRECT_QUERY_STRING'] = $uri->getQuery();
        }
        
        foreach (
            [
                static::REQUEST_SITE_HEADER,
                static::REQUEST_SLUG_HEADER,
            ] as $header
        ) {
            unset($serverParams[strtoupper(Inflector::toUnderscore($header))]);
        }
        
        return $serverParams;
    }
    
    /**
     * Internal helper that checks if a specific header exists on the request and rewrites it into the
     * query parameters, if the configured parameter name is NOT already present there.
     * The header in question will always be removed
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   string                                    $headerName
     * @param   string                                    $queryKey
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function rewriteHeaderToQueryParam(ServerRequestInterface $request, string $headerName, string $queryKey): ServerRequestInterface
    {
        if ($request->hasHeader($headerName)) {
            $query = $request->getQueryParams();
            if (! isset($query[$queryKey])) {
                $query[$queryKey] = $request->getHeaderLine($headerName);
                $request = $request->withQueryParams($query);
            }
            
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $request = $request->withoutHeader($headerName);
        }
        
        return $request;
    }
    
    /**
     * Internal helper to resolve a language id or iso code into the correct site language
     *
     * @param   mixed                             $value
     * @param   \TYPO3\CMS\Core\Site\Entity\Site  $site
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    protected function resolveLanguage($value, Site $site): ?SiteLanguage
    {
        if (empty($value) && $value !== '0') {
            return null;
        }
        
        if (is_int($value)) {
            $value = (string)$value;
        }
        
        if (is_string($value)) {
            if (is_numeric($value) && strlen($value) <= 2) {
                return $this->context->language()->getLanguageById((int)$value, $site->getIdentifier());
            }
            
            if (ctype_alpha($value) && strlen($value) === 2) {
                foreach ($this->context->language()->getAllFrontendLanguages($site->getIdentifier()) as $lang) {
                    if ($lang->getTwoLetterIsoCode() === strtolower($value)) {
                        return $lang;
                    }
                }
            }
            
            throw new \League\Route\Http\Exception\BadRequestException('The given language "' . $value . '" seems to be invalid!');
        }
        
        return null;
    }
}