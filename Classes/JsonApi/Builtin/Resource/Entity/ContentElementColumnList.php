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
 * Last modified: 2019.09.26 at 18:18
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContext;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use LaborDigital\Typo3FrontendApi\Shared\ShortTimeMemoryTrait;

class ContentElementColumnList implements SelfTransformingInterface
{
    use FrontendApiContextAwareTrait;
    use ShortTimeMemoryTrait;

    /**
     * The page id of the contents
     *
     * @var int
     */
    protected $pid;

    /**
     * The list of raw content element records by their column id
     *
     * @var array
     * @see \LaborDigital\Typo3BetterApi\Page\PageService::getPageContents()
     */
    protected $contents;

    /**
     * The two char iso language code for this column list
     *
     * @var string
     */
    protected $languageCode;

    /**
     * ContentElementColumnList constructor.
     *
     * @param   int     $pid
     * @param   array   $contents
     * @param   string  $languageCode
     */
    public function __construct(int $pid, array $contents, string $languageCode)
    {
        $this->pid          = $pid;
        $this->contents     = $contents;
        $this->languageCode = $languageCode;
    }

    /**
     * Returns the page id of the contents
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * Returns the two char iso language code for this column list
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * Returns the list of raw content element records by their column id
     *
     * @return array
     * @see \LaborDigital\Typo3BetterApi\Page\PageService::getPageContents()
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return $this->remember(function () {
            $result = [];

            foreach ($this->getColumns() as $colId => $elements) {
                foreach ($elements as $element) {
                    $result[$colId][] = $element->asArray();
                }
            }

            return $result;
        }, 'array');
    }

    /**
     * Returns the list of all column instances and their nested content element instances as well
     *
     * @return ContentElement[][]
     */
    public function getColumns(): array
    {
        return $this->remember(function () {
            $result  = [];
            $context = $this->FrontendApiContext();
            foreach ($this->contents as $colId => $columnElements) {
                foreach ($columnElements as $columnElement) {
                    /** @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement $element */
                    $element = $context->getInstanceWithoutDi(
                        ContentElement::class,
                        [
                            ContentElement::TYPE_TT_CONTENT,
                            $columnElement['uid'],
                            $this->languageCode,
                        ]
                    );

                    if (! empty($columnElement['children'])) {
                        $element->children = $context->getInstanceWithoutDi(
                            static::class, [$this->pid, $columnElement['children'], $this->languageCode]
                        );
                    }

                    $result[$colId][] = $element;
                }
            }

            return $result;
        }, 'columns');
    }

    /**
     * Factory method to create a new instance of myself based on the result of the page service's getPageContents()
     * method
     *
     * @param   array  $contents
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElementColumnList
     * @see        \LaborDigital\Typo3BetterApi\Page\PageService::getPageContents()
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstanceFromPageContentsArray(array $contents): ContentElementColumnList
    {
        $languageCode = FrontendApiContext::getInstance()->getLanguageCode();

        return TypoContainer::getInstance()->get(static::class, ['args' => [$contents, $languageCode]]);
    }
}
