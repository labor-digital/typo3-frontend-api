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
 * Last modified: 2020.09.30 at 10:20
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Site\Configuration;


use LaborDigital\Typo3BetterApi\Link\LinkService;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageLinks;

interface SiteLinkProviderInterface
{

    /**
     * Should register the provided links directly to the PageLinks object
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageLinks  $pageLinks
     * @param   \LaborDigital\Typo3BetterApi\Link\LinkService                             $linkService
     */
    public function provideLinks(PageLinks $pageLinks, LinkService $linkService): void;
}
