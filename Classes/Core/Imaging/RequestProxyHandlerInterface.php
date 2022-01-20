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
 * Last modified: 2022.01.20 at 12:03
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Imaging;


interface RequestProxyHandlerInterface
{
    /**
     * Must handle the given request object by dumping the resolved image information into the STDOUT
     *
     * @param   string                                   $redirectTarget  The redirect target url either starting with / for a local file
     *                                                                    or an absolute url
     * @param   \LaborDigital\T3fa\Core\Imaging\Request  $request
     *
     * @return void
     * @todo can we use PSR Responses here, without sacrificing too much performance?
     */
    public function settle(string $redirectTarget, Request $request): void;
}