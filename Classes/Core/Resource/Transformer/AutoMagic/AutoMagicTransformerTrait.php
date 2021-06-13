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
 * Last modified: 2021.06.08 at 17:09
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic;


trait AutoMagicTransformerTrait
{
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic\AutoTransformer
     */
    protected $autoTransformer;
    
    public function injectAutoTransformer(AutoTransformer $autoTransformer): void
    {
        $this->autoTransformer = $autoTransformer;
    }
    
    protected function autoTransform($value, array $options = [])
    {
        // @todo docs
        return $this->autoTransformer->transform($value, $options);
    }
    
    /**
     * Allows reliable transformation of link references inside of string values.
     * It will convert all TYPO3 link definitions into a real link
     *
     * @param   string  $input
     *
     * @return string
     */
    protected function transformLinkReferences(string $input): string
    {
        return $this->autoTransformer->transformLinkReferences($input);
    }
}