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
 * Last modified: 2019.09.18 at 12:11
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource;


use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\NotImplementedException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Page;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Strategy\ResourceStrategy;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer\PageTransformer;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\AbstractResourceController;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository;
use LaborDigital\Typo3FrontendApi\Shared\ModelHydrationTrait;
use League\Route\Http\Exception\BadRequestException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;

class PageController extends AbstractResourceController {
	use CommonServiceLocatorTrait;
	use ModelHydrationTrait;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository
	 */
	protected $repository;
	
	/**
	 * PageController constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository $repository
	 */
	public function __construct(ResourceDataRepository $repository) {
		$this->repository = $repository;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function configureResource(ResourceConfigurator $configurator, ExtConfigContext $context): void {
		$configurator->setTransformerClass(PageTransformer::class);
		$configurator->addClass(Page::class);
		$configurator->addAdditionalRoute("/bySlug", "bySlugAction")->setStrategy(ResourceStrategy::class);
	}
	
	/**
	 * @inheritDoc
	 */
	public function resourceAction(ServerRequestInterface $request, int $id, ResourceControllerContext $context) {
		// Extract filters
		$loadedLanguageCodes = Arrays::makeFromStringList($context->getQuery()->get("loadedLanguageCodes", ""));
		$currentLayout = $context->getQuery()->get("currentLayout", "");
		$refreshCommon = Arrays::makeFromStringList($context->getQuery()->get("refreshCommon", ""));
		
		// Get the additional
		return Page::makeInstance($id, $currentLayout, $loadedLanguageCodes, $refreshCommon);
	}
	
	/**
	 * The main entry point for page requests.
	 * It receives a frontend slug as "slug" parameter and renders the page data for it.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface                                    $request
	 * @param                                                                             $foo
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext $context
	 *
	 * @return mixed
	 * @throws \League\Route\Http\Exception\BadRequestException
	 */
	public function bySlugAction(ServerRequestInterface $request, $foo, ResourceControllerContext $context) {
		// Get the slug from the query
		$slug = $context->getQuery()->get("slug");
		if (empty($slug)) throw new BadRequestException("This endpoint expects a slug parameter!");
		
		// Handle the request by the default method
		return $this->resourceAction($request, $this->Tsfe->getTsfe()->id, $context);
	}
	
	/**
	 * @inheritDoc
	 */
	public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context) {
		throw new NotImplementedException();
	}
}