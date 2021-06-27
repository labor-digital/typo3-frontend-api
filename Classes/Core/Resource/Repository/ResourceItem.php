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
 * Last modified: 2021.05.22 at 00:37
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Repository;


use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceAbstract;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ResourceItem extends AbstractResourceElement
{
    /**
     * Returns the fractal resource item that represents this resource
     *
     * @return \League\Fractal\Resource\Item
     */
    public function getFractalItem(): Item
    {
        $item = GeneralUtility::makeInstance(Item::class,
            $this->raw,
            $this->transformerFactory->getTransformer($this->raw, false),
            $this->resourceType
        );
        
        if ($this->getMeta() !== null) {
            $item->setMeta($this->getMeta());
        }
        
        return $item;
    }
    
    /**
     * @inheritDoc
     */
    protected function getFractalElement(): ResourceAbstract
    {
        return $this->getFractalItem();
    }
}