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
 * Last modified: 2021.06.22 at 12:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Link;


use FastRoute\RouteParser\Std;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\Routing\Util\RequestRewriter;
use Neunerlei\Arrays\Arrays;
use Neunerlei\PathUtil\Path;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class Dumper implements PublicServiceInterface
{
    
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * First level cache of resolved base urls by their site identifier
     *
     * @var array
     */
    protected static $baseUrlCache = [];
    
    public function __construct(LinkService $linkService, TypoContext $typoContext)
    {
        $this->linkService = $linkService;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Converts the provided link into a stringified version
     *
     * @param   \LaborDigital\T3fa\Core\Link\ApiLink  $apiLink
     *
     * @return string
     */
    public function build(ApiLink $apiLink): string
    {
        $baseUrl = $this->buildBaseUrl($apiLink);
        $path = $this->buildPath($apiLink);
        $slug = $this->buildSlug($apiLink);
        $queryParams = $this->buildQueryParams($slug, $apiLink->getQueryParams());
        
        if ($apiLink->getSite()) {
            $queryParams[RequestRewriter::REQUEST_SITE_QUERY_KEY] = $apiLink->getSite();
        }
        if ($apiLink->getLanguage()) {
            $queryParams[RequestRewriter::REQUEST_LANG_QUERY_KEY] = $apiLink->getLanguage()->getLanguageId();
        }
        if ($slug && $slug !== '/') {
            $queryParams[RequestRewriter::REQUEST_SLUG_QUERY_KEY] = $slug;
        }
        
        $query = http_build_query($queryParams);
        
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/') . rtrim('?' . $query, '?');
    }
    
    /**
     * Builds the api path based on either the route name or the provided, static path
     *
     * @param   \LaborDigital\T3fa\Core\Link\ApiLink  $apiLink
     *
     * @return string
     * @throws \LaborDigital\T3fa\Core\Link\InvalidLinkException
     */
    protected function buildPath(ApiLink $apiLink): string
    {
        if ($apiLink->getRouteName()) {
            $routes = $this->typoContext->config()->getSiteBasedConfigValue('t3fa.routing.routes', []);
            foreach ($routes as $route) {
                if ($route['name'] === $apiLink->getRouteName()) {
                    return $this->buildRoutePath($route['path'], $apiLink->getRouteArguments());
                }
            }
            throw new InvalidLinkException('Failed to build link, there is no route with name: "' . $apiLink->getRouteName() . '"');
        }
        
        return $apiLink->getPath() ?? '/';
    }
    
    /**
     * This method is basically a carbon copy of a suggested implementation from khalyomedes implementation here:
     * https://github.com/nikic/FastRoute/issues/66#issuecomment-692212392
     * It takes a route path and replaces the arguments in it
     *
     * @param   string  $route
     * @param   array   $arguments
     *
     * @return string
     * @throws \LaborDigital\T3fa\Core\Link\InvalidLinkException
     */
    protected function buildRoutePath(string $route, array $arguments): string
    {
        $url = '';
        
        foreach ((new Std())->parse($route) as $routeData) {
            foreach ($routeData as $data) {
                if (is_string($data)) {
                    // This is a string, so nothing to replace inside of it
                    $url .= $data;
                } elseif (is_array($data)) {
                    // This is an array, so it contains in first the name of the parameter, and in second the regular expression.
                    // Example, [0 => "name", 1 => "[^/]"]
                    [$parameterName, $regularExpression] = $data;
                    
                    $parameterValue = null;
                    
                    if (isset($arguments[$parameterName])) {
                        // If the parameter name is found by its key in the $parameters parameter, we use it
                        $parameterValue = $arguments[$parameterName];
                        
                        // We remove it from the remaining placeholders values
                        unset($arguments[$parameterName]);
                    } elseif (isset($arguments[0])) {
                        // Else, we take the first parameter in the $parameters parameter
                        $parameterValue = $arguments[0];
                        
                        // We remove it from the remaining available placeholders values
                        array_shift($arguments);
                    } else {
                        throw new InvalidLinkException('The parameter ' . $parameterName . ' for route: "' . $route . '" is missing');
                    }
                    
                    // Checking if the value found matches the regular expression of the associated route parameter
                    $matches = [];
                    $success = preg_match('/' . str_replace('/', "\/", $regularExpression) . '/', (string)$parameterValue, $matches);
                    
                    if ($success !== 1 || (isset($matches[0]) && $parameterValue != $matches[0])) {
                        throw new InvalidLinkException('The parameter ' . $parameterName .
                                                       ' does not matches regular expression ' .
                                                       $regularExpression . ' for route: "' . $route . '"');
                    }
                    
                    $url .= $parameterValue;
                }
            }
        }
        
        return $url;
    }
    
    /**
     * Builds the slug part of the
     *
     * @param   \LaborDigital\T3fa\Core\Link\ApiLink  $apiLink
     *
     * @return string|null
     */
    protected function buildSlug(ApiLink $apiLink): ?string
    {
        if ($apiLink->getSlug()) {
            return $apiLink->getSlug();
        }
        
        if ($apiLink->getSlugLinkBuilder()) {
            $builder = $apiLink->getSlugLinkBuilder();
            
            if ($builder instanceof Link) {
                $builder = $builder->withLanguage($apiLink->getLanguage());
                
                return $builder->build(['relative']);
            }
            
            if ($builder instanceof UriBuilder) {
                $builder->setCreateAbsoluteUri(false);
                
                return $builder->buildFrontendUri();
            }
        }
        
        return null;
    }
    
    /**
     * Collects the list of query parameters for the link.
     * Automatically extracts all query parameters from the slug and puts them into the global query list
     *
     * @param   string|null  $slug
     * @param   array|null   $queryParams
     *
     * @return array
     */
    protected function buildQueryParams(?string &$slug, ?array $queryParams): array
    {
        $params = [];
        if ($slug) {
            $uri = Path::makeUri($slug);
            if (! empty($uri->getQuery())) {
                parse_str($uri->getQuery(), $params);
                $slug = (string)$uri->withQuery('');
            }
        }
        
        if ($queryParams) {
            $params = Arrays::merge($queryParams, $params, 'nn');
        }
        
        return $params;
    }
    
    /**
     * Builds the base url for all api links
     *
     * @param   \LaborDigital\T3fa\Core\Link\ApiLink  $link
     *
     * @return string
     */
    protected function buildBaseUrl(ApiLink $link): string
    {
        $siteIdentifier = $link->getSite() ?? $this->typoContext->site()->getCurrent()->getIdentifier();
        if (isset(static::$baseUrlCache[$siteIdentifier])) {
            return static::$baseUrlCache[$siteIdentifier];
        }
        
        $host = $this->typoContext->config()->getConfigValue('typo.site.' . $siteIdentifier . '.t3fa.site.apiHost');
        
        if (! is_string($host)) {
            $request = $this->getApiRequest();
            if (! $request) {
                return static::$baseUrlCache[$siteIdentifier][$siteIdentifier] = '';
            }
            
            $uri = $request->getUri();
            $host = $uri->getScheme() . '://' . $uri->getHost();
        }
        
        
        $baseUri = $this->typoContext->config()->getConfigValue('t3fa.routing.apiPath');
        
        return static::$baseUrlCache[$siteIdentifier] = $host . '/' . trim($baseUri, '/');
    }
    
    
    /**
     * Helper to retrieve the correct request instance
     *
     * @return \Psr\Http\Message\ServerRequestInterface|null
     */
    protected function getApiRequest(): ?ServerRequestInterface
    {
        $request = $this->typoContext->request()->getRootRequest();
        if ($request && $request->getAttribute('originalRequest') !== null) {
            return $request->getAttribute('originalRequest');
        }
        
        return $request;
    }
}