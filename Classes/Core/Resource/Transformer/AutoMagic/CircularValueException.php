<?php
/*
 * Copyright 2022 LABOR.digital
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
 * Last modified: 2022.02.02 at 19:03
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic;


use LaborDigital\T3fa\Core\Resource\Transformer\TransformerScope;

class CircularValueException extends \Exception
{
    public static function makeInstance(object $value): self
    {
        $circularPath = array_map(static function (object $v): string {
            $id = is_callable([$v, 'getUid']) ? 'uid(' . $v->getUid() . ')' : spl_object_id($v);
            
            return get_class($v) . '::' . $id;
        }, array_merge(TransformerScope::$path, [$value]));
        
        return new self('Circular transformation: ' . implode(' -> ', $circularPath), spl_object_id($value));
    }
}