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
 * Last modified: 2021.06.09 at 14:19
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\Schema;


trait SchemaAwareTransformerTrait
{
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Schema\SchemaRegistry
     */
    protected $schemaRegistry;
    
    public function injectSchemaRegistry(SchemaRegistry $registry): void
    {
        $this->schemaRegistry = $registry;
    }
    
    /**
     * Returns the schema for the given value which describes how to transform
     * the value into an array
     *
     * @param   object  $value  This can be literary any value
     *
     * @return \LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema
     */
    protected function getSchema(object $value): TransformationSchema
    {
        return $this->schemaRegistry->getSchema($value);
    }
}