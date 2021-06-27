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
 * Last modified: 2021.06.21 at 12:30
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\LayoutObject;


use LaborDigital\T3fa\Api\Resource\Factory\LayoutObject\LayoutObjectContext;

interface LayoutObjectInterface
{
    /**
     * Must generate the data array for the layout object to be converted into a json
     *
     * @param   \LaborDigital\T3fa\Api\Resource\Factory\LayoutObject\LayoutObjectContext  $context
     *
     * @return array
     */
    public function generate(LayoutObjectContext $context): array;
}