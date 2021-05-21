<?php
/**
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
 * Last modified: 2020.05.11 at 23:54
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer;


use DateTime;
use LaborDigital\Typo3BetterApi\Link\TypoLink;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractSpecialObjectTransformer;
use Neunerlei\TinyTimy\DateTimy;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class DefaultSpecialObjectTransformer extends AbstractSpecialObjectTransformer
{

    /**
     * @inheritDoc
     */
    public function transformValue($value)
    {
        // Handle date objects
        if ($value instanceof DateTime) {
            return (new DateTimy($value))->formatJs();
        }

        // Handle link objects
        if ($value instanceof TypoLink) {
            // Announce argument instances as tags for the caching system
            $this->FrontendApiContext()->CacheService()
                 ->announceTags($value->getArgs())
                 ->announceTag($value->getPid() !== null ? 'pages_' . $value->getPid() : null);

            return $value->build();
        }
        if ($value instanceof UriInterface) {
            return (string)$value;
        }
        if ($value instanceof UriBuilder) {
            return $value->buildFrontendUri();
        }

        // Not found
        return null;
    }

}
