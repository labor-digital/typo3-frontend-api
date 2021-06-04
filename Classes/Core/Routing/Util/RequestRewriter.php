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
 * Last modified: 2021.06.04 at 18:00
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing\Util;


use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use Neunerlei\Inflection\Inflector;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class RequestRewriter
{
    public const REQUEST_SLUG_HEADER = 'x-t3fa-slug';
    public const REQUEST_SLUG_QUERY_KEY = 'slug';
    public const REQUEST_SITE_HEADER = 'x-t3fa-site-identifier';
    public const REQUEST_SITE_HOST_HEADER = 'x-t3fa-site-host';
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
    
    public function __construct(TypoContext $context, ServerRequestFactory $requestFactory)
    {
        $this->context = $context;
        $this->requestFactory = $requestFactory;
    }
    
    /**
     * Rewrites the given server request and creates a TYPO3 conform request object out of it.
     * This should prevent issues where TYPO3 has issues to resolve the correct page
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function rewrite(ServerRequestInterface $request): ServerRequestInterface
    {
        $uri = $this->rewriteUri($request->getUri(), $request);
        
        $typoRequest = $this->requestFactory->createServerRequest(
            $request->getMethod(),
            $uri,
            $this->makeServerParams($uri)
        );
        
        $typoRequest = $typoRequest->withCookieParams($request->getCookieParams());
        $typoRequest = $typoRequest->withAttribute('originalRequest', $request);
        
        foreach ($request->getAttributes() as $key => $attribute) {
            $typoRequest = $typoRequest->withAttribute($key, $attribute);
        }
        
        $typoRequest = $typoRequest->withAttribute('normalizedParams', NormalizedParams::createFromRequest($typoRequest));
        
        // @todo an event would be nice here
        
        return $typoRequest;
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
        if (isset($request->getQueryParams()[static::REQUEST_LANG_QUERY_KEY])) {
            $lang = $this->resolveLanguage($request->getQueryParams()[static::REQUEST_LANG_QUERY_KEY], $request->getAttribute('site'));
        }
        
        if (! isset($lang) && $request->hasHeader(static::REQUEST_LANG_HEADER)) {
            $lang = $this->resolveLanguage(
                $request->getHeaderLine(static::REQUEST_LANG_HEADER),
                $request->getAttribute('site')
            );
        }
        
        if ($lang !== null) {
            return $request->withAttribute('language', $lang);
        }
        
        return $request;
    }
    
    /**
     * Rewrites the uri based on the provided "host" and "slug" settings in the api request
     *
     * @param   \Psr\Http\Message\UriInterface            $uri
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\UriInterface
     */
    protected function rewriteUri(UriInterface $uri, ServerRequestInterface $request): UriInterface
    {
        return $this->rewriteHost(
            $this->rewriteSlug($uri, $request),
            $request
        );
    }
    
    /**
     * Rewrites the given "slug" to be part of the request we pass to TYPO3
     *
     * @param   \Psr\Http\Message\UriInterface            $uri
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\UriInterface
     */
    protected function rewriteSlug(UriInterface $uri, ServerRequestInterface $request): UriInterface
    {
        $query = $request->getQueryParams();
        if (! empty($query[static::REQUEST_SLUG_QUERY_KEY])) {
            return $this->makeNewSlugUri($uri, $query[static::REQUEST_SLUG_QUERY_KEY]);
        }
        
        if ($request->hasHeader(static::REQUEST_SLUG_HEADER)) {
            return $this->makeNewSlugUri($uri, $request->getHeaderLine(static::REQUEST_SLUG_HEADER));
        }
        
        return $this->makeNewSlugUri($uri, '/');
    }
    
    /**
     * Creates a new uri object which combines schema and host from the given $uri and the $slug
     * provided to the method.
     *
     * @param   \Psr\Http\Message\UriInterface  $uri
     * @param   string                          $slug
     *
     * @return \Psr\Http\Message\UriInterface
     * @throws \TYPO3\CMS\Core\Error\Http\BadRequestException
     */
    protected function makeNewSlugUri(UriInterface $uri, string $slug): UriInterface
    {
        try {
            return new Uri($uri->getScheme() . '://' . $uri->getHost() . '/' . ltrim($slug, '/'));
        } catch (\Throwable $e) {
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
        $query = $request->getQueryParams();
        if (! empty($query[static::REQUEST_SITE_QUERY_KEY])) {
            $siteIdentifier = $query[static::REQUEST_SITE_QUERY_KEY];
        }
        
        if (! isset($siteIdentifier) && $request->hasHeader(static::REQUEST_SITE_HEADER)) {
            $siteIdentifier = $request->getHeaderLine(static::REQUEST_SITE_HEADER);
        }
        
        if (isset($siteIdentifier)) {
            if (! $this->context->site()->has($siteIdentifier)) {
                throw new BadRequestException('The required site: "' . $siteIdentifier . '" does not exist');
            }
            
            $hostUri = $this->context->site()->get($siteIdentifier)->getBase();
        }
        
        if (! isset($hostUri) && $request->hasHeader(static::REQUEST_SITE_HOST_HEADER)) {
            $siteHost = $request->getHeaderLine(static::REQUEST_SITE_HOST_HEADER);
            try {
                $hostUri = new Uri($siteHost);
            } catch (\Throwable $e) {
                throw new BadRequestException('The required site host: "' . $siteHost . '" is an invalid url');
            }
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
    protected function makeServerParams(UriInterface $uri): array
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
                static::REQUEST_SITE_HOST_HEADER,
            ] as $header
        ) {
            unset($serverParams[strtoupper(Inflector::toUnderscore($header))]);
        }
        
        return $serverParams;
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