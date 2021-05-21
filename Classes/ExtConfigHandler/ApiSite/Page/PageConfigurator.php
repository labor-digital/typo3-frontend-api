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
 * Last modified: 2021.05.17 at 18:51
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use LaborDigital\T3ba\ExtConfig\ExtConfigException;

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
    
}