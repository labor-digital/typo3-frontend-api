<?php
declare(strict_types=1);
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
 * Last modified: 2020.11.25 at 18:38
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Transformation\Utils;


use LaborDigital\Typo3BetterApi\Domain\BetterQuery\RelatedRecordRow;
use LaborDigital\Typo3BetterApi\Domain\DbService\DbServiceException;
use LaborDigital\Typo3BetterApi\LazyLoading\LazyLoadingTrait;

trait ValueProxyTrait
{
    // @todo replace with static implementation in v10
    use LazyLoadingTrait;

    /**
     * Resolves lazy loading proxies and related record rows in the given value before returning the real value
     *
     * @param $value
     *
     * @return mixed
     */
    public function resolveRealValue($value)
    {
        // Nothing to do if the value is no object
        if (! is_object($value)) {
            return $value;
        }

        // Resolve lazy loading proxies
        $value = $this->lazyLoading->getRealValue($value);

        // Resolve related record rows
        if ($value instanceof RelatedRecordRow) {
            try {
                $value = $value->getModel();
            } catch (DbServiceException $e) {
                $value = $value->getRow();
            }
        }

        return $value;
    }
}
