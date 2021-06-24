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
 * Last modified: 2021.06.24 at 14:09
 */

declare(strict_types=1);

use LaborDigital\T3ba\Core\Kernel;
use LaborDigital\T3fa\Core\Imaging\RequestFactory;

const T3FA_IMAGING_ENDPOINT_PREFIX = '/imaging-api';

if (class_exists(Kernel::class) && getenv('T3FA_IMAGING_DISABLED') === false) {
    (static function () {
        foreach (['REDIRECT_URL', 'REQUEST_URI'] as $field) {
            /** @noinspection StrStartsWithCanBeUsedInspection */
            if (is_string($_SERVER[$field] ?? null) && strpos($_SERVER[$field], T3FA_IMAGING_ENDPOINT_PREFIX) === 0) {
                Kernel::addOnInitHook(static function () use ($field) {
                    $request = RequestFactory::makeRequest($_SERVER[$field]);
                    $request->settleIfPossible();
                });
                break;
            }
        }
    })();
}