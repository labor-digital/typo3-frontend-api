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
 * Last modified: 2019.08.13 at 14:20
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource;


use LaborDigital\Typo3BetterApi\Domain\Repository\BetterRepository;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\AbstractResourceController;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository;

class SysCategoryController extends AbstractResourceController
{

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * SysCategoryController constructor.
     *
     * @param   \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository  $categoryRepository
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritDoc
     */
    public static function configureResource(ResourceConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->addClass(Category::class);
        $configurator->setAllowedProperties(["title", "description"]);
    }

    /**
     * @inheritDoc
     */
    public function resourceAction(ServerRequestInterface $request, int $id, ResourceControllerContext $context)
    {
        return $this->categoryRepository->findByUid($id);
    }

    /**
     * @inheritDoc
     */
    public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context)
    {
        $wrap  = BetterRepository::getWrapper($this->categoryRepository);
        $query = $wrap->getQuery();


        // Add default filters and sorting
        $query = $this->addFilterConstraint($query, $context, ["pid"], []);

        // Done
        return $query;
    }
}
