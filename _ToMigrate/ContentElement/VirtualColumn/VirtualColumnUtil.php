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
 * Last modified: 2020.11.09 at 12:48
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\ContentElement\VirtualColumn;


use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigChildRepository;
use LaborDigital\Typo3FrontendApi\Domain\Table\Override\TtContentOverrides;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContext;
use Neunerlei\Arrays\Arrays;

class VirtualColumnUtil
{
    /**
     * For the most part the virtual columns work out of the box,
     * however when resolving file references in the backend, or creating extbase objects there are issues where typo3 expects
     * the "virtual" column names to exist in the tca. To provide a polyfill in the content element
     * controller we rewrite the vCol_ definitions the their internal column name so typo3 can resolve the
     * virtual columns without problems.
     *
     * @param   string    $cType
     * @param   callable  $wrapper
     *
     * @return mixed
     * @throws \JsonException
     */
    public static function runWithResolvedVColTca(string $cType, callable $wrapper)
    {
        // Skip if we don't have anything to do
        $columnMap = static::getContentElementConfig()->getVirtualColumnsFor($cType);
        if (empty($columnMap)) {
            return $wrapper();
        }

        // Store the original tca and prepare the reverting function
        $originalTca = json_encode(Arrays::getPath($GLOBALS, ['TCA', 'tt_content', 'columns'], []), JSON_THROW_ON_ERROR);

        try {
            // Remap the existing tca using the content element map

            foreach ($columnMap as $target => $real) {
                $GLOBALS['TCA']['tt_content']['columns'][$target]
                    = Arrays::getPath($GLOBALS, ['TCA', 'tt_content', 'columns', $real], []);
            }

            // Run the real code
            return $wrapper();
        } finally {
            $GLOBALS['TCA']['tt_content']['columns'] = json_decode($originalTca, true, 512, JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Helper to resolve the virtual columns in a row into the actual row array.
     *
     * @param   string  $cType             The cType of content element to resolve the columns for
     * @param   array   $row               The row to resolve the the virtual columns on
     * @param   bool    $removeVColPrefix  By default the virtual columns will be merged into the returned
     *                                     row including their vCol_... prefix still attached to them.
     *                                     You can merge them without that prefix when you set this to true
     *
     * @return array
     */
    public static function resolveVColsInRow(string $cType, array $row, bool $removeVColPrefix = false): array
    {
        // Skip if we don't have anything to do
        $columnMap = static::getContentElementConfig()->getVirtualColumnsFor($cType);
        if (empty($columnMap) || empty($row[TtContentOverrides::VIRTUAL_COLUMN_FIELD])) {
            return $row;
        }

        // Flip the map -> We want the vCol name as a lookup
        $columnMap = array_flip($columnMap);

        // Merge the v cols into the row
        $virtualColumnValues = Arrays::makeFromJson($row[TtContentOverrides::VIRTUAL_COLUMN_FIELD]);
        $rowFiltered         = $row;
        foreach ($virtualColumnValues as $k => $v) {
            if (isset($columnMap[$k])) {
                if ($removeVColPrefix) {
                    $rowFiltered[$columnMap[$k]] = $v;
                } else {
                    $rowFiltered[$k] = $v;
                }
            }
        }

        return $rowFiltered;
    }

    /**
     * Returns the instance of the content element child repository
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigChildRepository
     */
    protected static function getContentElementConfig(): ContentElementConfigChildRepository
    {
        return FrontendApiContext::getInstance()->ConfigRepository()->contentElement();
    }
}
