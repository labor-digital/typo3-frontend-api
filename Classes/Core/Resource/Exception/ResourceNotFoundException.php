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
 * Last modified: 2021.05.07 at 12:18
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Exception;


use LaborDigital\T3fa\Core\ErrorHandler\HttpConvertibleExceptionInterface;
use LaborDigital\T3fa\T3faException;
use League\Route\Http\Exception\HttpExceptionInterface;
use League\Route\Http\Exception\NotFoundException;

class ResourceNotFoundException extends T3faException implements HttpConvertibleExceptionInterface
{
    /**
     * @inheritDoc
     */
    public function getHttpException(): HttpExceptionInterface
    {
        return new NotFoundException($this->message, $this);
    }
    
}