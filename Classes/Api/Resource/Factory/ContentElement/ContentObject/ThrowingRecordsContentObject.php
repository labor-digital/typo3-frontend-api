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
 * Last modified: 2021.06.10 at 12:31
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\ContentElement\ContentObject;


use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use TYPO3\CMS\Frontend\ContentObject\RecordsContentObject;

class ThrowingRecordsContentObject extends RecordsContentObject
{
    /**
     * The pid of the last rendered element
     *
     * @var int|null
     */
    public static $lastRenderedPid;
    
    /**
     * @inheritDoc
     */
    public function render($conf = [])
    {
        static::$lastRenderedPid = null;
        
        $result = parent::render($conf);
        
        if (empty($result) && empty($this->data)) {
            throw new ResourceNotFoundException('There is no content element with the id: ' . $this->itemArray[0]['id']);
        }
        
        static::$lastRenderedPid = $this->data['tt_content'][$this->itemArray[0]['id'] ?? null]['pid'] ?? null;
        
        return $result;
    }
}