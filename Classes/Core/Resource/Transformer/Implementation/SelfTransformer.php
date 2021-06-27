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
 * Last modified: 2021.05.17 at 20:24
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Implementation;


use LaborDigital\T3fa\Core\Resource\Transformer\AbstractResourceTransformer;
use LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoMagicTransformerTrait;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\HybridSelfTransformingInterface;
use LaborDigital\T3fa\Core\Resource\Transformer\Special\SelfTransformingInterface;

class SelfTransformer extends AbstractResourceTransformer
{
    use AutoMagicTransformerTrait;
    
    /**
     * @inheritDoc
     */
    public function transform($value): array
    {
        $data = ['id' => null];
        if ($value instanceof SelfTransformingInterface) {
            $data = $value->asArray();
        }
        
        if ($value instanceof HybridSelfTransformingInterface) {
            $data = $this->autoTransform($data);
        }
        
        return $data;
    }
    
}