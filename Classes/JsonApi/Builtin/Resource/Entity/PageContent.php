<?php
declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.09.20 at 19:22
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use LaborDigital\Typo3FrontendApi\Shared\ShortTimeMemoryTrait;

class PageContent implements SelfTransformingInterface
{
    use FrontendApiContextAwareTrait;
    use ShortTimeMemoryTrait;

    /**
     * The page id we hold the layout data for
     *
     * @var int
     */
    protected $id;

    /**
     * The two char iso language code for this element
     *
     * @var string
     */
    protected $languageCode;

    /**
     * PageContent constructor.
     *
     * @param   int     $id  The pid of the page to gather the contents for
     * @param   string  $languageCode
     */
    public function __construct(int $id, string $languageCode)
    {
        $this->id           = $id;
        $this->languageCode = $languageCode;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $context = $this->FrontendApiContext();

        return $context->CacheService()->remember(
            function () {
                return [
                    'id'       => $this->id,
                    'children' => $this->getContents()->asArray(),
                ];
            },
            [__CLASS__, $this->id, $this->languageCode, $context->getCacheRelevantQueryParams()],
            ['tags' => ['page_' . $this->id, 'pages_' . $this->id]]
        );
    }

    /**
     * Returns the contents as object representation
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElementColumnList
     */
    public function getContents(): ContentElementColumnList
    {
        return $this->remember(function () {
            $context = $this->FrontendApiContext();

            return $context->getInstanceWithoutDi(ContentElementColumnList::class, [
                $this->id,
                $context->Page()->getPageContents($this->id),
                $this->languageCode,
            ]);
        });
    }

    /**
     * Factory method to create a new instance of myself
     *
     * @param   int  $id
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageContent
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(int $id): PageContent
    {
        return TypoContainer::getInstance()->get(static::class, ['args' => [$id]]);
    }
}
