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
 * Last modified: 2021.05.17 at 18:13
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Resource\Entity;


use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;

class PageRootLineEntity implements SelfTransformingInterface
{
    /**
     * The pid this root line applies to
     *
     * @var int
     */
    protected $pid;
    
    /**
     * The language uid used to generate this root line
     *
     * @var int
     */
    protected $language;
    
    /**
     * The entries that are available in this root line
     *
     * @var array
     */
    protected $entries;
    
    public function __construct(int $pid, int $language, array $entries)
    {
        $this->pid = $pid;
        $this->language = $language;
        $this->entries = $entries;
    }
    
    /**
     * Returns the page id of this root line
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->pid;
    }
    
    /**
     * Returns the language uid used to generate this root line
     *
     * @return int
     */
    public function getLanguage(): int
    {
        return $this->language;
    }
    
    /**
     * Returns the entries that are available in this root line
     *
     * @return array
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
    
    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return [
            'id' => $this->pid,
            'language' => $this->language,
            'entries' => $this->entries,
        ];
    }
    
}