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
 * Last modified: 2021.05.31 at 12:25
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Resource\Transformer;


use LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository;
use LaborDigital\T3fa\Core\Resource\Transformer\AbstractResourceTransformer;
use LaborDigital\T3fa\Resource\Entity\PageEntity;
use LaborDigital\T3fa\Resource\PageRootLine;
use League\Fractal\Resource\ResourceAbstract;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class PageTransformer extends AbstractResourceTransformer
{
    protected $availableIncludes = ['rootLine'];
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository
     */
    protected $repository;
    
    public function __construct(ResourceRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @inheritDoc
     *
     * @param   PageEntity  $value
     */
    public function transform($value): array
    {
        if (! $value instanceof PageEntity) {
            return ['id' => null];
        }
        
        // @todo finish this
        return [
            'id' => $value->getId(),
            'site' => $value->getSite()->getIdentifier(),
            'languages' => array_map(static function (SiteLanguage $language): string {
                return $language->getTwoLetterIsoCode();
            }, $value->getSite()->getAllLanguages()),
            'language' => $value->getLanguage()->getTwoLetterIsoCode(),
            'links' => $value->getLinks(),
        ];
    }
    
    public function includeRootLine(PageEntity $value): ResourceAbstract
    {
        return $this->autoIncludeItem(
            $this->repository->getResource(PageRootLine::class, $value->getId())
        );
    }
}