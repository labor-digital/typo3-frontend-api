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
 * Last modified: 2021.05.10 at 16:09
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer;


interface ResourceTransformerInterface extends TransformerInterface
{
    /**
     * Receives the value that MUST be returned in an array.
     * The result array MUST contain a "id" key, containing the unique id of this resource.
     *
     * @param $value
     *
     * @return array
     */
    public function transform($value): array;
    
    /**
     * Returns a list of field names that can be included for a resource
     *
     * @return array
     * @see https://fractal.thephpleague.com/transformers/#including-data
     * @see \League\Fractal\TransformerAbstract::getAvailableIncludes()
     */
    public function getAvailableIncludes(): array;
    
    /**
     * Returns a list of field names that will always be included in a resource
     *
     * @return array
     * @see https://fractal.thephpleague.com/transformers/#default-includes
     * @see \League\Fractal\TransformerAbstract::getDefaultIncludes()
     */
    public function getDefaultIncludes(): array;
}