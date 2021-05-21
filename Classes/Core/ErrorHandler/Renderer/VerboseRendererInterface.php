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
 * Last modified: 2021.05.12 at 16:28
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ErrorHandler\Renderer;


use LaborDigital\T3fa\Core\ErrorHandler\UnifiedError;

interface VerboseRendererInterface
{
    /**
     * Renders the given throwable to be displayed in a verbose error response
     *
     * @param   \Throwable  $throwable
     *
     * @return string
     */
    public function render(UnifiedError $unifiedError): string;
}