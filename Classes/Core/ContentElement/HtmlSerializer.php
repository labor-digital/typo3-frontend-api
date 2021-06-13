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
 * Last modified: 2021.06.07 at 19:09
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement;


use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;

class HtmlSerializer
{
    /**
     * Takes both arrays and "serializes" them as a json wrapped in a html script tag
     *
     * @param   array  $data  The main payload to be serialized
     * @param   array  $meta  Optional metadata to be transferred along side the data
     *
     * @return string
     */
    public static function serialize(array $data, array $meta = []): string
    {
        $metaJson = htmlentities(SerializerUtil::serializeJson($meta), ENT_QUOTES);
        
        return '<script type="text/json" data-html-serialized="' . $metaJson . '">
        ' . SerializerUtil::serializeJson($data) . '
        </script>';
    }
    
    /**
     * Takes a string that potentially contains a html serialized value and tires to "unserialize" it.
     * The result is either null if the content could not be deserialized or an array where
     * [0] is the unserialized value and [1] additional meta information that have been passed along.
     *
     * @param   string  $content
     *
     * @return array|null
     */
    public static function unserialize(string $content): ?array
    {
        if (stripos($content, 'data-html-serialized') === false) {
            return null;
        }
        
        $content = trim($content);
        
        preg_match('~<script type="text/json" data-html-serialized="(.*?)">(.*?)</script>~s', $content, $m);
        if (! is_array($m)) {
            return null;
        }
        [, $meta, $json] = $m;
        $meta = SerializerUtil::unserializeJson(html_entity_decode($meta));
        $json = SerializerUtil::unserializeJson($json);
        
        return [$json, $meta];
    }
}