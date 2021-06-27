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
 * Last modified: 2021.06.23 at 12:28
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api\Translation;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use LaborDigital\T3ba\Tool\Translation\Translator;
use Neunerlei\Configuration\State\ConfigState;

class TranslationConfigurator extends AbstractExtConfigConfigurator implements NoDiInterface
{
    
    /**
     * @var \LaborDigital\T3ba\Tool\Translation\Translator
     */
    private $translator;
    
    /**
     * The list of registered translation label files, or namespaces that should be provided
     *
     * @var array
     */
    protected $labelFiles = [];
    
    /**
     * Defines if plural forms should be compiled into an array under the same key
     *
     * @var bool
     */
    protected $pluralsAsArray = true;
    
    /**
     * Vue i180 can not work with %s placeholders, if this option is enabled,
     * the translation provider will automatically convert the placeholders into numeric
     * curly braced equivalents. e.g. Hello %s becomes Hello {0}
     *
     * @var bool
     */
    protected $convertSprintfPlaceholders = true;
    
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    
    /**
     * Registers a new label file that should be added to the provided translation labels under the
     * /api/resources/translation endpoint. All labels in all registered files will be compiled into a
     * single list. Overlaps will be resolved silently by overwriting keys.
     *
     * @param   string  $fileOrNamespace  This can be either a fully qualified path an EXT:... path or just the
     *                                    translation context you registered
     *
     * @return $this
     */
    public function registerLabelFile(string $fileOrNamespace): self
    {
        $fileOrNamespace = $this->prepareLabelFileName($fileOrNamespace);
        $this->labelFiles[md5($fileOrNamespace)] = $fileOrNamespace;
        
        return $this;
    }
    
    /**
     * Removes a previously registered label file from the list
     *
     * @param   string  $fileOrNamespace  This can be either a fully qualified path an EXT:... path or just the
     *                                    translation context you registered
     *
     * @return $this
     */
    public function removeLabelFile(string $fileOrNamespace): self
    {
        $fileOrNamespace = $this->prepareLabelFileName($fileOrNamespace);
        unset($this->labelFiles[md5($fileOrNamespace)]);
        
        return $this;
    }
    
    /**
     * Returns the list of all registered label files
     *
     * @return array
     */
    public function getLabelFiles(): array
    {
        return array_values($this->labelFiles);
    }
    
    /**
     * Returns true if plural forms should be compiled into an array under the same key
     *
     * @return bool
     */
    public function isPluralsAsArray(): bool
    {
        return $this->pluralsAsArray;
    }
    
    /**
     * Configures the flag that controls if plural forms should be compiled into an array under the same key
     *
     * @param   bool  $pluralsAsArray  True to enable the feature, false to disable it (TRUE is the default)
     */
    public function setPluralsAsArray(bool $pluralsAsArray): void
    {
        $this->pluralsAsArray = $pluralsAsArray;
    }
    
    /**
     * Vue i180 can not work with %s placeholders, if this option is enabled,
     * the translation provider will automatically convert the placeholders into numeric
     * curly braced equivalents. e.g. Hello %s becomes Hello {0}
     *
     * @return bool
     */
    public function isConvertSprintfPlaceholders(): bool
    {
        return $this->convertSprintfPlaceholders;
    }
    
    /**
     * Configures the flag which controls if the translation provider should convert sprintf placeholders into
     * curly braced placeholders instead.
     *
     * Vue i180 can not work with %s placeholders, if this option is enabled,
     * the translation provider will automatically convert the placeholders into numeric
     * curly braced equivalents. e.g. Hello %s becomes Hello {0}
     *
     * @param   bool  $convertSprintfPlaceholders  rue to enable the feature, false to disable it (TRUE is the default)
     */
    public function setConvertSprintfPlaceholders(bool $convertSprintfPlaceholders): void
    {
        $this->convertSprintfPlaceholders = $convertSprintfPlaceholders;
    }
    
    /**
     * @inheritDoc
     */
    public function finish(ConfigState $state): void
    {
        $labelFilesBackup = $this->labelFiles;
        $this->labelFiles = array_values($this->labelFiles);
        parent::finish($state);
        $this->labelFiles = $labelFilesBackup;
    }
    
    /**
     * Internal helper to convert the given file name or namespace into a unified file name
     *
     * @param   string  $fileOrNamespace
     *
     * @return string
     */
    protected function prepareLabelFileName(string $fileOrNamespace): string
    {
        $fileOrNamespace = $this->context->replaceMarkers($fileOrNamespace);
        
        if ($this->translator->hasNamespace($fileOrNamespace)) {
            $fileOrNamespace = $this->translator->getNamespaceFile($fileOrNamespace);
        }
        
        if (basename($fileOrNamespace) === $fileOrNamespace) {
            $fileOrNamespace = 'EXT:{{extKey}}/Resources/Private/Language/' . $fileOrNamespace;
            $fileOrNamespace = $this->context->replaceMarkers($fileOrNamespace);
        }
        
        if (str_starts_with($fileOrNamespace, 'LLL:')) {
            $fileOrNamespace = substr($fileOrNamespace, 4);
        }
        
        return $fileOrNamespace;
    }
}