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
 * Last modified: 2021.06.21 at 19:45
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Entity;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;

class ContentElementEntity implements NoDiInterface, SelfTransformingInterface
{
    /**
     * The page id we hold the representation for
     *
     * @var int|string
     */
    protected $id;
    
    /**
     * The prepared resource attributes
     *
     * @var array
     */
    protected $attributes = [];
    
    public function __construct($id, array $attributes)
    {
        $this->id = $id;
        $this->attributes = $attributes;
    }
    
    /**
     * Returns the uid id this object represents
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Returns the language code used to generate the content element
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
     * May contain the children of this element.
     * This is used when you content element is something like a grid element.
     * IMPORTANT: If not directly filled by the content element controller,
     * the children are only defined when retrieving the "pageContent" resource
     *
     * @return array|null
     * @see \LaborDigital\T3ba\Tool\Page\PageService::getPageContents() for the content syntax
     */
    public function getChildren(): ?array
    {
        return $this->attributes['children'] ?? null;
    }
    
    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $data = array_merge(
            [
                'id' => $this->id,
            ],
            $this->attributes
        );
        
        // Make sure that those keys are always transferred as object
        foreach (['children', 'data', 'initialState'] as $key) {
            if (is_array($data[$key] ?? null)) {
                $data[$key] = (object)$data[$key];
            }
        }
        
        return $data;
    }
}