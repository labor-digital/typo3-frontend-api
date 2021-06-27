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
 * Last modified: 2021.06.23 at 13:18
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Api\Resource\Entity\TranslationEntity;
use LaborDigital\T3fa\Api\Resource\Factory\Translation\TranslationFactory;
use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\Core\Resource\ResourceInterface;
use LaborDigital\T3fa\ExtConfigHandler\Api\Resource\ResourceConfigurator;
use Throwable;

class TranslationResource implements ResourceInterface
{
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * @var \LaborDigital\T3fa\Api\Resource\Factory\Translation\TranslationFactory
     */
    protected $factory;
    
    public function __construct(TypoContext $typoContext, TranslationFactory $factory)
    {
        $this->typoContext = $typoContext;
        $this->factory = $factory;
    }
    
    /**
     * @inheritDoc
     */
    public static function configure(ResourceConfigurator $configurator, SiteConfigContext $context): void
    {
        $configurator->registerClass(TranslationEntity::class);
    }
    
    /**
     * @inheritDoc
     */
    public function findSingle($id, ResourceContext $context)
    {
        try {
            if ($id === 'current') {
                $language = $this->typoContext->language()->getCurrentFrontendLanguage();
            } elseif (is_numeric($id)) {
                $language = $this->typoContext->language()->getLanguageById((int)$id);
            } else {
                $language = null;
                foreach ($this->typoContext->language()->getAllFrontendLanguages() as $_language) {
                    if ($_language->getTwoLetterIsoCode() === $id) {
                        $language = $_language;
                        break;
                    }
                }
            }
            
            if (! isset($language)) {
                throw new ResourceNotFoundException();
            }
            
            return $this->factory->make($language, $this->typoContext->site()->getCurrent());
        } catch (Throwable $e) {
            throw new ResourceNotFoundException();
        }
    }
    
    /**
     * @inheritDoc
     */
    public function findCollection(ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        $languages = [];
        $site = $this->typoContext->site()->getCurrent();
        
        foreach ($this->typoContext->language()->getAllFrontendLanguages() as $language) {
            $languages[] = $this->factory->make($language, $site);
        }
        
        return $languages;
    }
    
}