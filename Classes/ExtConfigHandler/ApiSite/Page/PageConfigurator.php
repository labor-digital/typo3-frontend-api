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
 * Last modified: 2021.05.31 at 11:55
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use LaborDigital\T3ba\ExtConfig\ExtConfigException;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\Link\PageLinkProviderInterface;

class PageConfigurator extends AbstractExtConfigConfigurator
{
    /**
     * The list of additional "pages" table fields which will be added to root line resource entries
     *
     * @var array
     */
    protected $additionalRootLineFields = [];
    
    /**
     * The list of provider classes that add additional data to entries of the root line resource
     *
     * @var array
     */
    protected $rootLineDataProviders = [];
    
    /**
     * The list of link provider classes that generate additional links when the "page" resource is retrieved
     *
     * @var array
     */
    protected $linkProviders = [];
    
    /**
     * Adds the given fields to the list of additional "pages" table fields which will be added to root line resource entries
     *
     * @param   array  $fields
     *
     * @return $this
     * @see \LaborDigital\T3fa\Resource\Entity\PageRootLineEntity
     */
    public function registerAdditionalRootLineFields(array $fields): self
    {
        return $this->setAdditionalRootLineFields(
            array_merge($this->additionalRootLineFields, $fields)
        );
    }
    
    /**
     * Updates the list of additional "pages" table fields which will be added to root line resource entries
     *
     * @param   array  $fields
     *
     * @return $this
     */
    public function setAdditionalRootLineFields(array $fields): self
    {
        $this->additionalRootLineFields = array_unique($fields);
        
        return $this;
    }
    
    /**
     * Returns the list of additional "pages" table fields which will be added to root line resource entries
     *
     * @return array
     */
    public function getAdditionalRootLineFields(): array
    {
        return $this->additionalRootLineFields;
    }
    
    /**
     * Used to register a root line data provider. Data providers are called once for every entry in the root line.
     * They receive the already prepared root line entry and can additional, dynamic data you can't implement
     * using just raw database fields.
     *
     * The class has to implement the RootLineDataProviderInterface interface
     *
     * @param   string  $class
     *
     * @return $this
     * @throws \LaborDigital\T3ba\ExtConfig\ExtConfigException
     * @see \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\RootLineDataProviderInterface
     */
    public function registerRootLineDataProvider(string $class): self
    {
        if (! in_array(RootLineDataProviderInterface::class, class_implements($class), true)) {
            throw new ExtConfigException(
                'The given root line data provider class "' . $class . '" has to implement the required interface: ' .
                RootLineDataProviderInterface::class);
        }
        
        $this->rootLineDataProviders = array_unique(
            array_merge($this->rootLineDataProviders, [$class])
        );
        
        return $this;
    }
    
    /**
     * Removes a previously registered data provider class from the list
     *
     * @param   string  $class
     *
     * @return $this
     */
    public function removeRootLineDataProvider(string $class): self
    {
        unset($this->rootLineDataProviders[$class]);
        
        return $this;
    }
    
    /**
     * Returns the list of all currently registered root line data providers
     *
     * @return array
     */
    public function getRootLineDataProviders(): array
    {
        return $this->rootLineDataProviders;
    }
    
    /**
     * Allows you to register a static link provider. The provider is called once for every page
     * It allows you to add additional links to the "link" node of the "page" resource. This can be used
     * to provide static urls for your project. The given class has to implement the SiteLinkProviderInterface.
     *
     * @param   string  $linkProviderClass  The class to register as link provider
     *
     * @return $this
     *
     * @see PageLinkProviderInterface
     */
    public function registerLinkProvider(string $linkProviderClass): self
    {
        if (! class_exists($linkProviderClass) ||
            ! in_array(PageLinkProviderInterface::class, class_implements($linkProviderClass), true)) {
            throw new \InvalidArgumentException(
                'Invalid link provider "' . $linkProviderClass . '" given! The class has to exist, and implement: "' .
                PageLinkProviderInterface::class . '"');
        }
        $this->linkProviders[md5($linkProviderClass)] = $linkProviderClass;
        
        return $this;
    }
    
    /**
     * Removes a previously registered link provider class from the list
     *
     * @param   string  $linkProviderClass
     *
     * @return $this
     */
    public function removeLinkProvider(string $linkProviderClass): self
    {
        unset($this->linkProviders[md5($linkProviderClass)]);
        
        return $this;
    }
    
    /**
     * Returns the list of all registered link provider classes
     *
     * @return array
     */
    public function getLinkProviders(): array
    {
        return array_values($this->linkProviders);
    }
}