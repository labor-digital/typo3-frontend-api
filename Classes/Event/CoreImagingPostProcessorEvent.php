<?php
/*
 * Copyright 2022 LABOR.digital
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
 * Last modified: 2022.05.09 at 06:09
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;


use LaborDigital\Typo3BetterApi\FileAndFolder\FileInfo\FileInfo;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingContext;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Resource\ProcessedFile;

class CoreImagingPostProcessorEvent extends AbstractImagingPostProcessorEvent
{
    /**
     * The reference of the processed source image
     *
     * @var \TYPO3\CMS\Core\Resource\ProcessedFile
     */
    protected $processed;

    /**
     * The reference of the processed webp image
     *
     * @var \Symfony\Component\Process\Process|null
     */
    protected $processedWebP;

    /**
     * The configuration provided to the processor
     *
     * @var array
     */
    protected $config;

    public function __construct(
        array $definition,
        FileInfo $fileInfo,
        ImagingContext $context,
        string $defaultRedirect,
        ?string $webPRedirect,
        ProcessedFile $processed,
        ?ProcessedFile $processedWebP
    ) {
        parent::__construct($definition, $fileInfo, $context, $defaultRedirect, $webPRedirect);
        $this->processed     = $processed;
        $this->processedWebP = $processedWebP;
        $this->config        = $config;
    }

    /**
     * Returns the reference of the processed source image
     *
     * @return \TYPO3\CMS\Core\Resource\ProcessedFile
     */
    public function getProcessed(): ProcessedFile
    {
        return $this->processed;
    }

    /**
     * Returns the reference of the processed webp image
     *
     * @return \Symfony\Component\Process\Process|null
     */
    public function getProcessedWebP(): ?Process
    {
        return $this->processedWebP;
    }
}
