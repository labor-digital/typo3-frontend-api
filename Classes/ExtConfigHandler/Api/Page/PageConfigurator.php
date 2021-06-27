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
 * Last modified: 2021.06.23 at 12:04
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api\Page;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use LaborDigital\T3ba\ExtConfig\ExtConfigException;
use LaborDigital\T3fa\Domain\DataModel\Page\DefaultPageDataModel;
use LaborDigital\T3fa\ExtConfigHandler\Api\Page\Link\PageLinkProviderInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class PageConfigurator extends AbstractExtConfigConfigurator implements NoDiInterface
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
     * The extbase model class to resolve the page data with
     *
     * @var string
     */
    protected $dataModelClass = DefaultPageDataModel::class;
    
    /**
     * A list of database fields that should be inherited from the parent pages if their current value is empty
     *
     * @var array
     */
    protected $dataSlideFields = [];
    
    /**
     * Adds the given fields to the list of additional "pages" table fields which will be added to root line resource entries
     *
     * @param   array  $fields
     *
     * @return $this
     * @see \LaborDigital\T3fa\Api\Resource\Entity\PageEntity
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
     * @see \LaborDigital\T3fa\ExtConfigHandler\Api\Page\RootLineDataProviderInterface
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
            throw new InvalidArgumentException(
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
    
    /**
     * Sets the extbase model class to resolve the page data with
     *
     * @param   string  $class  An extbase model class name that represents the page data.
     *                          The model class must extend the AbstractEntity
     *
     * @return $this
     *
     * @see DefaultPageDataModel
     * @see \LaborDigital\T3ba\Tool\Tca\ContentType\Domain\AbstractDataModel
     */
    public function setDataModelClass(string $class): self
    {
        if (! class_exists($class)) {
            throw new InvalidArgumentException('The given page data model: "' . $class . '" does not exist!');
        }
        
        if (! in_array(AbstractEntity::class, class_parents($class), true)) {
            throw new InvalidArgumentException('The given page data model: "' . $class . '" has to extend the ' . AbstractEntity::class . ' class!');
        }
        
        $this->dataModelClass = $class;
        
        return $this;
    }
    
    /**
     * Returns the configured extbase model class to resolve the page data with
     *
     * @return string
     */
    public function getDataModelClass(): string
    {
        return $this->dataModelClass;
    }
    
    /**
     * Used to configure a list of database fields that should be inherited from the parent pages if their current value is empty.
     * Useful for page media or social media tags to inherit data easily from the parent page.
     *
     * Slide fields work similar to this {@link https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/ContentObjects/Content/Index.html?highlight=slide#slide}
     * There is currently no way to use a "collect" mode.
     *
     * @param   array  $fields  A list of database field names to slide from the parent page if they are empty in the current page
     *
     * @return $this
     */
    public function setDataSlideFields(array $fields): self
    {
        $this->dataSlideFields = $fields;
        
        return $this;
    }
    
    /**
     * Returns the list of configured database fields that should be inherited from parent pages.
     *
     * @return array
     * @see setDataSlideFields()
     */
    public function getDataSlideFields(): array
    {
        return $this->dataSlideFields;
    }
}