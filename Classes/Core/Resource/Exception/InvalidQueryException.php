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
 * Last modified: 2021.05.07 at 10:22
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Exception;


use Throwable;

class InvalidQueryException extends InvalidRequestException
{
    /**
     * @inheritDoc
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if ($message === '') {
            $message = 'The given resource query is invalid';
        }
        
        parent::__construct($message, $code, $previous);
    }
    
}