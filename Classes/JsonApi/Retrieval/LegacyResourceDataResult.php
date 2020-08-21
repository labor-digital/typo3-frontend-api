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
 * Last modified: 2020.05.22 at 19:26
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Retrieval;


use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * Class LegacyResourceDataResult
 *
 * @package    LaborDigital\Typo3FrontendApi\JsonApi\Retrieval
 * @deprecated Temporary, legacy adapter to transition from arrays to the ResourceDataResult objects. Will be removed in v10
 */
class LegacyResourceDataResult extends ResourceDataResult implements ArrayAccess, IteratorAggregate
{
    protected $legacyStorage = [];

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->asArray());
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $array = parent::asArray();

        return array_merge($array, $this->legacyStorage);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->asArray()[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->legacyStorage[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->legacyStorage[$offset]);
    }

}
