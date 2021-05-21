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


use LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\DefaultContentElementModel;
use LaborDigital\Typo3FrontendApi\ContentElement\VirtualColumn\VirtualColumnUtil;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\Arrays;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class ContentElementRepository
{

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * @var \TYPO3\CMS\Core\Service\FlexFormService
     */
    protected $flexFormService;

    /**
     * ContentElementRepository constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     * @param   \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper              $dataMapper
     * @param   \TYPO3\CMS\Core\Service\FlexFormService                               $flexFormService
     */
    public function __construct(FrontendApiConfigRepository $configRepository, DataMapper $dataMapper, FlexFormService $flexFormService)
    {
        $this->configRepository = $configRepository;
        $this->dataMapper       = $dataMapper;
        $this->flexFormService  = $flexFormService;
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
        $cType = Arrays::getPath($row, ['CType']);
        if (empty($cType)) {
            throw new ContentElementException('The given row could not be hydrated, as it does not specify a CType field!');
        }

        // Resolve the model class
        $modelClass = $this->configRepository->contentElement()->getContentElementConfig($cType, 'modelClass');
        if (empty($modelClass)) {
            $modelClass = DefaultContentElementModel::class;
        }

        // Unpack the virtual columns
        $resolvedRow = VirtualColumnUtil::resolveVColsInRow($cType, $row, true);
        // @todo remove this in v10
        $legacyRow = VirtualColumnUtil::resolveVColsInRow($cType, $row);

        // Map the row to the model
        return VirtualColumnUtil::runWithResolvedVColTca($cType,
            function () use ($modelClass, $resolvedRow, $legacyRow) {
                $mapped = $this->dataMapper->map($modelClass, [$resolvedRow]);
                /** @var \LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel $model */
                $model = reset($mapped);

                $model->_setProperty('__raw', $resolvedRow);
                $model->_setProperty('__flex', $this->resolveFlexFormColumns($resolvedRow));
                $model->_setProperty('__legacyRawUnmapped', $legacyRow);

                return $model;
            });
    }

    /**
     * Internal helper to resolve the flex form columns into the __flex magic storage key
     *
     * @param   array  $row
     *
     * @return array
     */
    protected function resolveFlexFormColumns(array $row): array
    {
        $colConfig = $GLOBALS['TCA']['tt_content']['columns'];
        $flexCols  = [];
        foreach ($colConfig as $col => $conf) {
            // Check if this is a flex form column
            if (empty($row[$col])) {
                continue;
            }
            $type = Arrays::getPath($colConfig, [$col, 'config', 'type']);
            if ($type !== 'flex') {
                continue;
            }

            // Parse the content
            $value = $row[$col];
            $value = $this->flexFormService->convertFlexFormContentToArray($value);

            // Store the column
            $flexCols[$col] = $value;
        }

        return $flexCols;
    }
}
