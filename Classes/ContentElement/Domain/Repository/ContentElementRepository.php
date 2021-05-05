<?php
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
 * Last modified: 2019.08.28 at 14:17
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Domain\Repository;


use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel;
use LaborDigital\Typo3FrontendApi\Shared\Hydrator\Hydrator;

class ContentElementRepository
{
    /**
     * @var \LaborDigital\Typo3FrontendApi\Shared\Hydrator\Hydrator
     */
    protected $hydrator;

    /**
     * ContentElementRepository constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     * @param   \LaborDigital\Typo3FrontendApi\Shared\Hydrator\Hydrator               $hydrator
     * @param   \TYPO3\CMS\Core\Service\FlexFormService                               $flexFormService
     */
    public function __construct(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * Receives a row of the tt_content table and tries to map it into a ext base content element model.
     * The model class is resolved using the typoScript of the content element and the TCA array.
     *
     * @param   array  $row  The database row to convert into a model instance
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException
     */
    public function hydrateRow(array $row): AbstractContentElementModel
    {
        return $this->hydrator->hydrateObject('', 'tt_content', $row);
    }
}
