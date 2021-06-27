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
 * Last modified: 2021.05.05 at 12:17
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Shared\Hydrator;


class TcaUtil
{
    /**
     * Helper to extract a value from a given row, which handles all possible oddities of TYPO3
     *
     * @param   array   $row  The database row to extract the value from
     * @param   string  $key  The column name that should be extracted
     *
     * @return int|string
     */
    public static function getRowValue(array $row, string $key)
    {
        $value = $row[$key] ?? '';

        if (is_array($value)) {
            $value = reset($value);
        }

        if (! is_string($value) && ! is_numeric($value)) {
            $value = '';
        }

        return $value;
    }
}
