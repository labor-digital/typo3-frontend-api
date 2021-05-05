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
 * Last modified: 2021.05.05 at 11:03
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Shared\Hydrator;


use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class DataMapperAdapter extends DataMapper
{
    /**
     * Extracts the currently set data map factory from the given data mapper instance
     *
     * @param   \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper  $mapper
     *
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    public static function getFactory(DataMapper $mapper): DataMapFactory
    {
        return $mapper->dataMapFactory;
    }

    /**
     * Updates the data map factory in the given data mapper instance
     *
     * @param   \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper      $mapper
     * @param   \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory  $factory
     */
    public static function setFactory(DataMapper $mapper, DataMapFactory $factory): void
    {
        $mapper->dataMapFactory = $factory;
    }
}
