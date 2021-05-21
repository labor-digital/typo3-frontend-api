<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.03.30 at 21:50
 */

namespace LaborDigital\Typo3FrontendApi\Event;

/**
 * Class PageDataPageInfoFilterEvent
 *
 * Emitted when the PageData entity is generated.
 * It can be used to filter the page row before it is passed to the deeper levels of the post processing
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class PageDataPageInfoFilterEvent
{
    /**
     * The unique id of the page the data should be filtered for
     *
     * @var int
     */
    protected $uid;

    /**
     * The raw page data as an array to filter
     *
     * @var array
     */
    protected $row;

    /**
     * The map of fields and their parent page uids to map references correctly
     *
     * @var array
     */
    protected $slideFieldPidMapping;

    /**
     * PageDataPageInfoFilterEvent constructor.
     *
     * @param   int    $uid
     * @param   array  $row
     */
    public function __construct(int $uid, array $row, array $slideFieldPidMapping)
    {
        $this->uid                  = $uid;
        $this->row                  = $row;
        $this->slideFieldPidMapping = $slideFieldPidMapping;
    }

    /**
     * Returns the unique id of the page the data should be filtered for
     *
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Returns the map of fields and their parent page uids to map references correctly
     *
     * @return array
     */
    public function getSlideFieldPidMapping(): array
    {
        return $this->slideFieldPidMapping;
    }

    /**
     * Returns the raw page data as an array to filter
     *
     * @return array
     */
    public function getRow(): array
    {
        return $this->row;
    }

    /**
     * Updates the raw page data as an array to filter
     *
     * @param   array  $row
     *
     * @return PageDataPageInfoFilterEvent
     */
    public function setRow(array $row): PageDataPageInfoFilterEvent
    {
        $this->row = $row;

        return $this;
    }


}
