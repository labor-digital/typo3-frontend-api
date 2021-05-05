<?php
declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.09.18 at 19:01
 */

namespace LaborDigital\Typo3FrontendApi\Shared;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Shared\Hydrator\Hydrator;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

trait ModelHydrationTrait
{

    /**
     * This helper leverages the extBase data mapper to create a new entity class for a given row.
     *
     * @param   string  $modelClass  The name of the class to use as entity
     * @param   string  $tableName   The table name of the $row
     * @param   array   $row         The row to map to the model class
     *
     * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
     * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
     */
    protected function hydrateModelObject(string $modelClass, string $tableName, array $row): AbstractEntity
    {
        return TypoContainer::getInstance()->get(Hydrator::class)
                            ->hydrateObject($modelClass, $tableName, $row);
    }
}
