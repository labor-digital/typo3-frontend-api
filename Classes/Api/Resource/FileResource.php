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
 * Last modified: 2021.06.02 at 20:35
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3ba\Tool\Fal\FalService;
use LaborDigital\T3fa\Api\Resource\Transformer\FileTransformer;
use LaborDigital\T3fa\Core\Resource\Exception\NoCollectionException;
use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\Core\Resource\ResourceInterface;
use LaborDigital\T3fa\ExtConfigHandler\Api\Resource\ResourceConfigurator;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;

class FileResource implements ResourceInterface
{
    /**
     * @var \LaborDigital\T3ba\Tool\Fal\FalService
     */
    protected $falService;
    
    public function __construct(FalService $falService)
    {
        $this->falService = $falService;
    }
    
    /**
     * @inheritDoc
     */
    public static function configure(ResourceConfigurator $configurator, SiteConfigContext $context): void
    {
        $configurator->registerTransformer(FileTransformer::class);
        $configurator->registerClass(\TYPO3\CMS\Core\Resource\File::class);
        $configurator->registerClass(\TYPO3\CMS\Core\Resource\ProcessedFile::class);
        $configurator->registerClass(\TYPO3\CMS\Extbase\Domain\Model\File::class);
    }
    
    /**
     * @inheritDoc
     */
    public function findSingle($id, ResourceContext $context)
    {
        try {
            return $this->falService->getFileReference($id);
        } catch (ResourceDoesNotExistException $e) {
            throw new ResourceNotFoundException($e->getMessage(), $e);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function findCollection(ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        throw new NoCollectionException($context->getResourceType());
    }
    
}