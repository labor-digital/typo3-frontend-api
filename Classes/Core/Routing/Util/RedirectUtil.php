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
 * Last modified: 2021.06.13 at 23:16
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Routing\Util;


class RedirectUtil
{
    /**
     * Builds the "redirect" child array of the attributes inside a page object
     *
     * @param   string       $url
     * @param   string|null  $target
     * @param   int|null     $statusCode
     *
     * @return array[]
     */
    public static function makeRedirectAttribute(string $url, ?string $target = null, ?int $statusCode = null): array
    {
        return [
            'redirect' => [
                'url' => $url,
                'target' => empty($target) ? '_self' : $target,
                'statusCode' => $statusCode ?? 301,
            ],
        ];
    }
    
    /**
     * Builds the whole data array to be returned as a json response
     *
     * @param   string       $url
     * @param   string|null  $target
     * @param   int|null     $statusCode
     *
     * @return array[]
     */
    public static function makeRedirectData(string $url, ?string $target = null, ?int $statusCode = null): array
    {
        return [
            'data' => [
                'type' => 'redirect',
                'id' => md5($url),
                'attributes' => static::makeRedirectAttribute($url, $target, $statusCode),
            ],
        ];
    }
}