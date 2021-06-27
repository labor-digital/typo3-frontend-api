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
 * Last modified: 2021.05.05 at 10:59
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Shared\Hydrator;


use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;

class CacheContextAwareDataMapFactory extends DataMapFactory
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    protected $concreteFactory;

    /**
     * @var string
     */
    protected $cacheContext;

    /**
     * CacheContextAwareDataMapFactory constructor.
     *
     * @param   \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory  $concreteFactory
     */
    public function __construct(DataMapFactory $concreteFactory)
    {
        $this->concreteFactory = $concreteFactory;
    }

    /**
     * Allows the outside world to inject the cache context
     *
     * @param   string  $context
     */
    public function setCacheContext(string $context): void
    {
        $this->cacheContext = $context;
    }

    /**
     * @inheritDoc
     */
    public function buildDataMap($className)
    {
        $storageKey = str_replace('\\', '%', $className) . '_' . $this->cacheContext;
        $that       = $this->concreteFactory;
        if (isset($that->dataMaps[$storageKey])) {
            return $that->dataMaps[$storageKey];
        }

        $dataMap = $that->dataMapCache->get($storageKey);
        if ($dataMap === false) {
            if ($that->configurationManager instanceof ConfigurationManager) {
                ConfigManagerAdapter::flushCache($that->configurationManager);
            }

            $dataMap = $that->buildDataMapInternal($className);
            $that->dataMapCache->set($storageKey, $dataMap);
        }

        $that->dataMaps[$storageKey] = $dataMap;

        return $dataMap;
    }
}
