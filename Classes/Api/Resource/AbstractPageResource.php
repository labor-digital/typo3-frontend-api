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
 * Last modified: 2021.06.21 at 14:04
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource;


use LaborDigital\T3fa\Core\Resource\AbstractResource;
use LaborDigital\T3fa\Core\Resource\Exception\InvalidIdException;
use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

abstract class AbstractPageResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    public function findSingle($id, ResourceContext $context)
    {
        if (! is_numeric($id) && $id !== 'current') {
            throw new InvalidIdException();
        }
        
        $typoContext = $context->getTypoContext();
        
        if ($id === 'current') {
            $id = $typoContext->pid()->getCurrent();
        }
        
        try {
            return $this->resolveSingle(
                (int)$id,
                $typoContext->language()->getCurrentFrontendLanguage(),
                $typoContext->site()->getCurrent());
        } catch (PageNotFoundException $exception) {
            throw new ResourceNotFoundException('There is no page with the given id: ' . $id);
        }
    }
    
    /**
     * Internal method to resolve the actual entity based on the implementation
     *
     * @param   int                                        $id
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage   $language
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteInterface  $site
     *
     * @return mixed
     */
    abstract protected function resolveSingle(int $id, SiteLanguage $language, SiteInterface $site);
}