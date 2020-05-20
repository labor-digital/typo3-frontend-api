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
 * Last modified: 2019.08.12 at 22:42
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Controller;


use LaborDigital\Typo3FrontendApi\JsonApi\Pagination\SelfPaginatingInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

interface ResourceControllerInterface {
	
	/**
	 * Should handle the lookup of a single resource from the repository and return the result.
	 * The result may be an array, any iterable (including ObjectStorage) or any DB result / query object
	 * The transformation will be handled automatically.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface                                    $request
	 * @param int                                                                         $id
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext $context
	 *
	 * @return mixed
	 */
	public function resourceAction(ServerRequestInterface $request, int $id, ResourceControllerContext $context);
	
	/**
	 * Should handle the lookup of a resource collection from the repository and return the result.
	 * The result may be an array, any iterable (including ObjectStorage) or any DB result / query object
	 * The transformation will be handled automatically.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface                                      $request
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext $context
	 *
	 * @return array|iterable|QueryResultInterface|SelfPaginatingInterface
	 */
	public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context);
}