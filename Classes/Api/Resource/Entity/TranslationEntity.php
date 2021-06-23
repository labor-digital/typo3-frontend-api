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
 * Last modified: 2021.06.23 at 13:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Entity;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;

class TranslationEntity implements NoDiInterface, SelfTransformingInterface
{
    
    /**
     * The two char language code used to generate the translation
     *
     * @var string
     */
    protected $id;
    
    /**
     * The prepared resource attributes
     *
     * @var array
     */
    protected $attributes = [];
    
    public function __construct(
        string $id,
        array $attributes
    )
    {
        $this->id = $id;
        $this->attributes = $attributes;
    }
    
    /**
     * Returns the two char language code used to generate the translation
     *
     * @return int
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Returns the language code used to generate the page
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->id;
    }
    
    /**
     * Returns the site identifier which contains this page
     *
     * @return string
     */
    public function getSiteIdentifier(): string
    {
        return $this->attributes['meta']['site'] ?? '';
    }
    
    /**
     * Returns the translation labels contained in this translation
     *
     * @return array
     */
    public function getLabels(): array
    {
        return $this->attributes['labels'] ?? [];
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
                'id' => $this->id,
            ],
            $this->attributes
        );
    }
    
}