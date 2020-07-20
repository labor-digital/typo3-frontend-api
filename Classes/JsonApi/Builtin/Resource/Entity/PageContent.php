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


use LaborDigital\Typo3BetterApi\Container\ContainerAwareTrait;
use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\Page\PageService;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;

class PageContent implements SelfTransformingInterface
{
    use ContainerAwareTrait;
    
    /**
     * The internal column list we use as data handler
     *
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElementColumnList
     */
    protected $columnList;
    
    /**
     * The page id we hold the layout data for
     *
     * @var int
     */
    protected $id;
    
    /**
     * PageContent constructor.
     *
     * @param   int  $id  The pid of the page to gather the contents for
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }
    
    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return [
            'id'       => $this->id,
            'children' => $this->getContents()->asArray(),
        ];
    }
    
    /**
     * Returns the contents as object representation
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElementColumnList
     */
    public function getContents(): ContentElementColumnList
    {
        if (isset($this->columnList)) {
            return $this->columnList;
        }
        
        return $this->columnList = $this->getInstanceOf(ContentElementColumnList::class,
            [$this->getSingletonOf(PageService::class)->getPageContents($this->id)]);
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
