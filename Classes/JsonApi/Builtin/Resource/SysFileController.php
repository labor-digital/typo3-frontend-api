<?php
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.09.04 at 18:18
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\NotImplementedException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer\FileResourceTransformer;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\AbstractResourceController;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;

class SysFileController extends AbstractResourceController
{
    public static function configureResource(ResourceConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->setClasses([
            File::class,
            \TYPO3\CMS\Extbase\Domain\Model\File::class,
            ProcessedFile::class,
        ]);
        $configurator->setTransformerClass(FileResourceTransformer::class);
    }

    public function resourceAction(ServerRequestInterface $request, int $id, ResourceControllerContext $context)
    {
        try {
            $ref = $this->FalFiles->getFile($id);

            return $ref;
        } catch (ResourceDoesNotExistException $e) {
            throw new NotFoundException($e->getMessage(), $e);
        }
    }

    public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context)
    {
        throw new NotImplementedException();
    }

}
