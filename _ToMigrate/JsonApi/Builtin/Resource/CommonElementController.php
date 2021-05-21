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
 * Last modified: 2019.09.20 at 14:00
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\CommonElement;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\AbstractResourceController;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;

class CommonElementController extends AbstractResourceController
{
    /**
     * @inheritDoc
     */
    public static function configureResource(ResourceConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->getResourceRoute()->setPath('/{layout}/{id}');
        $configurator->addClass(CommonElement::class);
        $configurator->setPageSize(0);
    }

    /**
     * @inheritDoc
     */
    public function resourceAction(ServerRequestInterface $request, $id, ResourceControllerContext $context)
    {
        $elements = $this->FrontendApiContext()->getCurrentSiteConfig()->commonElements;
        $layout   = $context->getParams()['layout'];
        // Fallback to default if the layout does not exist
        if (! isset($elements[$layout])) {
            $layout = 'default';
        }
        $key = (string)$context->getParams()['id'];
        if (! isset($elements[$layout]) || ! isset($elements[$layout][$key])) {
            throw new NotFoundException('There is no common object with the given key: ' . $key);
        }

        return $this->getInstanceOf(CommonElement::class, [$layout, $key]);
    }

    /**
     * @inheritDoc
     */
    public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context)
    {
        // Get the allowed keys
        $requestedKeys = Arrays::makeFromStringList(Arrays::getPath($context->getQuery()->getFilters(), 'key', ''));
        $layout        = (string)$context->getQuery()->get('layout', 'default');

        // Generate the collection
        return $this->FrontendApiContext()->getCurrentSiteConfig()->getCommonElementInstances($layout, $requestedKeys);
    }

}
