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
 * Last modified: 2021.05.17 at 20:06
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;

interface RootLineDataProviderInterface extends PublicServiceInterface
{
    
    /**
     * Used to add additional, dynamic data to your root line entries.
     * The method is called once for every step in the root line
     *
     * @param   int    $pid          The pid of the page that is currently generated
     * @param   array  $entry        The currently prepared entry of the root line
     * @param   array  $rawRootLine  The raw root line as array
     *
     * @return array Should return the updated $rootLineEntry with it's data filtered
     */
    public function addData(int $pid, array $entry, array $rawRootLine): array;
}
