<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.04.19 at 18:11
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\NotImplementedException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PagePidConfig;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\AbstractResourceController;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use Psr\Http\Message\ServerRequestInterface;

class PagePidConfigController extends AbstractResourceController {
	/**
	 * @inheritDoc
	 */
	public static function configureResource(ResourceConfigurator $configurator, ExtConfigContext $context): void {
		$configurator->addClass(PagePidConfig::class);
	}
	
	/**
	 * @inheritDoc
	 */
	public function resourceAction(ServerRequestInterface $request, int $id, ResourceControllerContext $context) {
		return $this->Simulator->runWithEnvironment(["pid" => $id], function () use ($id) {
			return $this->getInstanceOf(PagePidConfig::class, [$id]);
		});
	}
	
	/**
	 * @inheritDoc
	 */
	public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context) {
		throw new NotImplementedException();
	}
	
}