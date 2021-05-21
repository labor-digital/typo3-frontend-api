<?php
declare(strict_types=1);
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


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\NotImplementedException;
use LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Middleware\FrontendSimulation\FrontendSimulationMiddleware;
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

class PageController extends AbstractResourceController
{
    use ModelHydrationTrait;

    /**
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository
     */
    protected $repository;

    /**
     * PageController constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository  $repository
     */
    public function __construct(ResourceDataRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public static function configureResource(ResourceConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->setTransformerClass(PageTransformer::class);
        $configurator->addClass(Page::class);
        $configurator->addAdditionalRoute('/bySlug', 'bySlugAction')->setStrategy(ResourceStrategy::class);
    }

    /**
     * @inheritDoc
     */
    public function resourceAction(ServerRequestInterface $request, int $id, ResourceControllerContext $context)
    {
        // Extract filters
        $currentLayout       = $context->getQuery()->get('currentLayout', '');
        $refreshCommon       = Arrays::makeFromStringList($context->getQuery()->get('refreshCommon', ''));
        $loadedLanguageCodes = Arrays::makeFromStringList(
        // @todo remove loadedLanguageCodes in v10
            $context->getQuery()->get('loadedLanguages', $context->getQuery()->get('loadedLanguageCodes', ''))
        );
        $languageCode        = $this->FrontendApiContext()->getLanguageCode();

        // The frontend will submit the old language header (EN) even if the new page url given by ?slug=
        // is actually part of another language (PL). This is because the frontend does not know, the new page
        // is localized in another language at the point of the request.
        // We can utilize this behaviour by tracking the given header (EN) and comparing it with the new language
        // code, which will be the actual language of the page (PL) (because the router resolved the language for us).
        // With this we save the additional query parameter of "currentLanguage".
        // If no language header is set we assume there was no language change and fall back to the actual language code
        $currentLanguageCode = $context->getRequest()->getHeaderLine(FrontendSimulationMiddleware::REQUEST_LANGUAGE_HEADER);
        if (empty($currentLanguageCode)) {
            $currentLanguageCode = $languageCode;
        }

        return $this->FrontendApiContext()->getInstanceWithoutDi(
            Page::class,
            [$id, (string)$currentLayout, $loadedLanguageCodes, $refreshCommon, $languageCode, $currentLanguageCode]
        );
    }

    /**
     * The main entry point for page requests.
     * It receives a frontend slug as "slug" parameter and renders the page data for it.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface                                     $request
     * @param                                                                                $foo
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext  $context
     *
     * @return mixed
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    public function bySlugAction(ServerRequestInterface $request, $foo, ResourceControllerContext $context)
    {
        // Get the slug from the query
        $slug = $context->getQuery()->get('slug');
        if (empty($slug)) {
            throw new BadRequestException('This endpoint expects a slug parameter!');
        }

        // Handle the request by the default method
        return $this->resourceAction($request, (int)$this->Tsfe()->getTsfe()->id, $context);
    }

    /**
     * @inheritDoc
     */
    public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context)
    {
        throw new NotImplementedException();
    }
}
