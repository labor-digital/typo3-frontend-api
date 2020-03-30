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
 * Last modified: 2019.09.20 at 18:42
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Event\PageDataPageInfoFilterEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\SiteConfigAwareTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\Shared\ModelHydrationTrait;
use League\Route\Http\Exception\NotFoundException;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class PageData {
	use SiteConfigAwareTrait;
	use ModelHydrationTrait;
	
	/**
	 * The page id we hold the data for
	 * @var int
	 */
	protected $id;
	
	/**
	 * Holds the last page info array or is null
	 * @var array
	 */
	protected $pageInfo;
	
	/**
	 * Returns the page data representation as an object
	 * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
	 * @throws \League\Route\Http\Exception\NotFoundException
	 * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
	 */
	public function getData(): AbstractEntity {
		$pageClass = $this->getCurrentSiteConfig()->pageDataClass;
		if (!class_exists($pageClass)) throw new JsonApiException("The given page data class: $pageClass does not exist!");
		return $this->hydrateModelObject($pageClass, "pages", $this->getPageInfo());
	}
	
	/**
	 * Returns the raw page database row
	 * @return array
	 * @throws \League\Route\Http\Exception\NotFoundException
	 */
	public function getPageInfo(): array {
		if (isset($this->pageInfo)) return $this->pageInfo;
		$pageInfo = $this->Page->getPageInfo($this->id);
		if (empty($pageInfo)) throw new NotFoundException();
		$this->EventBus->dispatch(($e = new PageDataPageInfoFilterEvent($this->id, $pageInfo)));
		return $this->pageInfo = $e->getRow();
	}
	
	/**
	 * Factory method to create a new instance of myself
	 *
	 * @param int $id
	 *
	 * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData
	 */
	public static function makeInstance(int $id): PageData {
		$self = TypoContainer::getInstance()->get(static::class);
		$self->id = $id;
		return $self;
	}
}