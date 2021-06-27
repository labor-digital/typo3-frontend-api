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
 * Last modified: 2021.06.21 at 12:41
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Entity;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;

class LayoutObjectEntity implements NoDiInterface, SelfTransformingInterface
{
    /**
     * The unique identifier of this
     *
     * @var string
     */
    protected $identifier;
    
    /**
     * The prepared resource attributes
     *
     * @var array
     */
    protected $attributes = [];
    
    public function __construct(
        string $identifier,
        array $attributes
    )
    {
        $this->identifier = $identifier;
        $this->attributes = $attributes;
    }
    
    /**
     * Returns the unique identifier of this
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    /**
     * Returns the language code used to generate the object with
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->attributes['meta']['language'] ?? 'en';
    }
    
    /**
     * Returns the prepared resource attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return array_merge(
            [
                'id' => $this->identifier,
            ],
            $this->attributes
        );
    }
}