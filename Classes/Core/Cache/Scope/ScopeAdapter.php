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
 * Last modified: 2021.06.01 at 13:57
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache\Scope;


class ScopeAdapter extends Scope
{
    /**
     * Adapter to call the "semi-private" close() method on the scope
     *
     * @param   Scope  $scope
     * @param   int    $defaultLifetime  The default cache entry lifetime
     *
     * @return array
     */
    public static function closeScope(Scope $scope, int $defaultLifetime): array
    {
        return $scope->close($defaultLifetime);
    }
}