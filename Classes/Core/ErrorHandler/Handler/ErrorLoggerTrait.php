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
 * Last modified: 2021.06.07 at 17:34
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ErrorHandler\Handler;


use LaborDigital\T3fa\Core\ErrorHandler\UnifiedError;
use Psr\Log\LoggerInterface;

trait ErrorLoggerTrait
{
    /**
     * Internal helper to log an error
     *
     * @param   \Psr\Log\LoggerInterface  $logger
     * @param   UnifiedError              $error
     */
    protected function logError(LoggerInterface $logger, UnifiedError $error): void
    {
        $context = $error->getLogContext();
        $message = $error->getMessage();
        
        $statusCode = $error->getStatusCode();
        if ($statusCode >= 400) {
            if ($statusCode >= 500) {
                $logger->critical($message, $context);
            } elseif ($statusCode === 404) {
                $logger->warning($message, $context);
            } else {
                $logger->error($message, $context);
            }
        } else {
            $logger->info($message, $context);
        }
    }
    
}