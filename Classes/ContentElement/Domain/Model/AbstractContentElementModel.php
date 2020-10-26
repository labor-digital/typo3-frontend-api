<?php
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.08.28 at 14:18
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model;


use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

abstract class AbstractContentElementModel extends AbstractEntity
{

    /**
     * The map of column names and their virtual column name to map
     *
     * @var array
     */
    protected $__virtualColumnMap;

    /**
     * The raw database array that was used to create this model
     *
     * @var array
     */
    protected $__raw;

    /**
     * The raw database array where the vCols have been remapped to their shorter name
     *
     * @var array
     */
    protected $__rawRemapped;

    /**
     * Contains
     *
     * @var array
     */
    protected $__flex = [];

    /**
     * Returns the raw database array that was used to create this model
     *
     * @return array
     */
    public function getRaw(bool $remapVCols = false): array
    {
        if ($remapVCols) {
            if (is_array($this->__rawRemapped)) {
                return $this->__rawRemapped;
            }

            $this->__rawRemapped = $this->__raw;
            $map                 = array_flip($this->__virtualColumnMap);
            foreach ($this->__rawRemapped as $field => $value) {
                if (isset($map[$field])) {
                    $field = $map[$field];
                }
                $this->__rawRemapped[$field] = $value;
            }

            return $this->__rawRemapped;
        }

        return $this->__raw;
    }

    /**
     * Can be used to access the values of any flex form field in your configuration.
     * By default this method returns the full flex form array (if no path is given).
     * The method will combine all available flex form fields into a single array, that contains the matching field
     * names. You can use the $path attribute to select a specific path in your configuration
     *
     * @param   array|string|null  $path     If empty the whole flex form array is returned. Can be used to find a
     *                                       sub-section of the configuration.
     * @param   null|mixed         $default  Can be used to define a default value that is returned inf the given path was
     *                                       not found.
     *
     * @return array|mixed|null
     */
    public function getFlex($path = null, $default = null)
    {
        if (empty($path)) {
            return $this->__flex;
        }

        return Arrays::getPath($this->__flex, $path, $default);
    }

    /**
     * Allow magic access to all the raw properties of this model
     *
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        $raw = $this->getRaw();
        if (isset($this->__virtualColumnMap[$name])) {
            $name = $this->__virtualColumnMap[$name];
        }
        if (isset($raw[$name])) {
            return $raw[$name];
        }
        $name = Inflector::toDatabase($name);
        if (isset($this->__virtualColumnMap[$name])) {
            $name = $this->__virtualColumnMap[$name];
        }
        if (isset($raw[$name])) {
            return $raw[$name];
        }

        return null;
    }
}
