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
 * Last modified: 2021.06.24 at 18:32
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Event\Resource\Translation;


use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class TranslationLabelFilterEvent
{
    /**
     * The labels to filter in this event
     *
     * @var array
     */
    protected $labels;
    
    /**
     * The language object used to generate the labels with
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    protected $language;
    
    /**
     * The site object used to generate the labels with
     *
     * @var \TYPO3\CMS\Core\Site\Entity\SiteInterface
     */
    protected $site;
    
    public function __construct(array $labels, SiteLanguage $language, SiteInterface $site)
    {
        $this->labels = $labels;
        $this->language = $language;
        $this->site = $site;
    }
    
    /**
     * Returns the translation labels to filter in this event.
     * Note: The labels are already formatted into a nested, hierarchical array
     *
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }
    
    /**
     * Updates the translation labels to filter in this event.
     * Note: The labels provided MUST BE formatted into a nested, hierarchical array
     *
     * @param   array  $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = $labels;
    }
    
    /**
     * Returns the language object used to generate the labels with
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }
    
    /**
     * Returns the site object used to generate the labels with
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteInterface
     */
    public function getSite(): SiteInterface
    {
        return $this->site;
    }
    
    
}