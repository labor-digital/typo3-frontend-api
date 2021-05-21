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
 * Last modified: 2021.05.07 at 09:47
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Resource\Entity;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;

class PageEntity
{
    use ContainerAwareTrait;
    
    /**
     * The page id we hold the representation for
     *
     * @var int
     */
    protected $id;
    
    /**
     * Holds the page's root line array after it was resolved
     *
     * @var array|null
     */
    protected $rootLine;
    
    public function __construct(int $id)
    {
        $this->id = $id;
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
    
    public function getRootLine(): array
    {
        return [];
    }
}