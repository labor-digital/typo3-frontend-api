<?php
/*
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
 * Last modified: 2020.11.18 at 13:57
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache\KeyGeneration;


use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContext;

interface CacheKeyEnhancerInterface
{
    /**
     * Receives the prepared list of environment cache key arguments that you can enhance for your project's requirements.
     * This is useful if you have some kind of project-specific context you want to affect all generated cache keys.
     *
     * @param   array               $args                The prepared cache key arguments
     * @param   TypoContext         $typoContext         The typo context instance
     * @param   FrontendApiContext  $frontendApiContext  The frontend api context instance
     *
     * @return array MUST return the modified list of arguments
     */
    public function enhanceArgs(array $args, TypoContext $typoContext, FrontendApiContext $frontendApiContext): array;
}
