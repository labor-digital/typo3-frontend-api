<?php
/*
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.09.24 at 18:03
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Shared;


use GuzzleHttp\Psr7\ServerRequest;
use LaborDigital\Typo3BetterApi\Container\CommonDependencyTrait;
use LaborDigital\Typo3FrontendApi\Cache\CacheService;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;
use LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class FrontendApiContext implements SingletonInterface
{
    use CommonDependencyTrait {
        getInstanceOf as public;
        getSingletonOf as public;
        EventBus as public;
        TypoContext as public;
        Translation as public;
        Tsfe as public;
        Simulator as public;
        Links as public;
        Page as public;
    }

    /**
     * The singleton instance of the context
     *
     * @var self
     */
    protected static $instance;

    /**
     * Returns the frontend api configuration repository
     *
     * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    public function ConfigRepository(): FrontendApiConfigRepository
    {
        return $this->getSingletonOf(FrontendApiConfigRepository::class);
    }

    /**
     * Returns the frontend api caching service instance
     *
     * @return \LaborDigital\Typo3FrontendApi\Cache\CacheService
     */
    public function CacheService(): CacheService
    {
        return $this->getSingletonOf(CacheService::class);
    }

    /**
     * Returns the instance of the transformer factory
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory
     */
    public function TransformerFactory(): TransformerFactory
    {
        return $this->getSingletonOf(TransformerFactory::class);
    }

    /**
     * Returns the instance of the resource data repository
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository
     */
    public function ResourceDataRepository(): ResourceDataRepository
    {
        return $this->getSingletonOf(ResourceDataRepository::class);
    }

    /**
     * Returns the instance of the current server request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        $rootRequest = $this->TypoContext()->Request()->getRootRequest();
        if ($rootRequest !== null) {
            return $rootRequest;
        }

        return ServerRequest::fromGlobals();
    }

    /**
     * Returns the two char iso language code that is currently set for the frontend
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->TypoContext()->Language()->getCurrentFrontendLanguage()->getTwoLetterIsoCode();
    }

    /**
     * Returns the site configuration either for the current site or the global site if no specific site config was
     * found.
     *
     * @return \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteConfig
     */
    public function getCurrentSiteConfig(): SiteConfig
    {
        return $this->ConfigRepository()->site()->getCurrentSiteConfig();
    }

    /**
     * Creates a new instance of the given class, using the provided constructor arguments,
     * but will not apply any kind of dependency injection.
     *
     * @param   string  $class
     * @param   array   $args
     *
     * @return mixed
     */
    public function getInstanceWithoutDi(string $class, array $args = [])
    {
        return $this->Container()->getWithoutDi($class, $args);
    }

    /**
     * Returns the list of all query parameters that are considered relevant for caching page elements
     * like content elements or the whole page content array
     *
     * @return array
     */
    public function getCacheRelevantQueryParams(): array
    {
        return Arrays::without($this->getRequest()->getQueryParams(),
            array_merge(
                (array)($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] ?? []),
                [
                    'slug',
                    'include',
                    'page',
                    'sort',
                    'filter',
                    'fields',
                ]
            )
        );
    }

    /**
     * Returns the singleton instance of this context object
     *
     * @return static
     */
    public static function getInstance(): self
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }

        // @todo replace this with PSR container in v10
        static::$instance = GeneralUtility::makeInstance(ObjectManager::class)->get(__CLASS__);

        return static::$instance;
    }
}
