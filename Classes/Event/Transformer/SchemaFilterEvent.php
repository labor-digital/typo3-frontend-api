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
 * Last modified: 2021.06.25 at 18:56
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Event\Transformer;

use LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema;

/**
 * Class SchemaFilterEvent
 *
 * Emitted when the transformation schema factory generates a new schema instance.
 *
 * @package LaborDigital\T3fa\Event\Transformer
 */
class SchemaFilterEvent
{
    /**
     * The name of the class that this schema applies to
     *
     * @var string
     */
    protected $className;
    
    /**
     * The generated transformation schema
     *
     * @var \LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema
     */
    protected $schema;
    
    public function __construct(string $className, TransformationSchema $schema)
    {
        $this->className = $className;
        $this->schema = $schema;
    }
    
    /**
     * Returns the name of the class that this schema applies to
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
    
    /**
     * Returns the generated transformation schema
     *
     * @return \LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema
     */
    public function getSchema(): TransformationSchema
    {
        return $this->schema;
    }
    
    /**
     * Allows you to override the generated transformation schema
     *
     * @param   \LaborDigital\T3fa\Core\Resource\Transformer\Schema\TransformationSchema  $schema
     *
     * @return SchemaFilterEvent
     */
    public function setSchema(TransformationSchema $schema): self
    {
        $this->schema = $schema;
        
        return $this;
    }
}