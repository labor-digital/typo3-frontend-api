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
 * Last modified: 2021.06.10 at 12:47
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Entity;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Tool\Cache\CacheTagProviderInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;

class PageContentEntity implements NoDiInterface, SelfTransformingInterface, CacheTagProviderInterface
{
    /**
     * The page id we hold the representation for
     *
     * @var int
     */
    protected $id;
    
    /**
     * The prepared resource attributes
     *
     * @var array
     */
    protected $attributes = [];
    
    public function __construct(
        int $id,
        array $attributes
    )
    {
        $this->id = $id;
        $this->attributes = $attributes;
    }
    
    /**
     * Returns the page id this object represents
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Returns the language code used to generate the site
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
                'id' => $this->id,
            ],
            $this->attributes
        );
    }
    
    /**
     * @inheritDoc
     */
    public function getCacheTags(): array
    {
        return ['page_' . $this->id, 'pages_' . $this->id];
    }
    
    
}