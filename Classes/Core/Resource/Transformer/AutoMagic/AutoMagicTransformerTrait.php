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
 * Last modified: 2021.06.25 at 18:52
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
    
    /**
     * Can take virtually ANY value and convert it into a json compatible representation.
     *
     * @param   mixed       $value    The value to transform
     * @param   array|null  $options  Options for the transformation of objects. You can use the
     *                                options defined here {@Link AbstractResourceElement::asArray()}
     *                                Additionally: if $value is an array, you can define a special "byKey"
     *                                option which allows you to configure the options for each key of the
     *                                $value array separately. The options will be merged into the root level options.
     *                                This works ONLY for arrays not for iterable objects, tho!
     *
     * @return array|bool|float|int|mixed|string|null
     */
    protected function autoTransform($value, array $options = [])
    {
        return $this->autoTransformer->transform($value, $options, $this);
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