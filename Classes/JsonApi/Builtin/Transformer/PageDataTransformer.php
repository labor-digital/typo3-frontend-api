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
 * Last modified: 2019.09.20 at 18:44
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer;


use DOMDocument;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;
use Neunerlei\PathUtil\Path;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Seo\Canonical\CanonicalGenerator;
use TYPO3\CMS\Seo\MetaTag\MetaTagGenerator;

class PageDataTransformer extends AbstractResourceTransformer {
	/**
	 * @var \TYPO3\CMS\Seo\Canonical\CanonicalGenerator
	 */
	protected $canonicalGenerator;
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
	 */
	protected $context;
	
	/**
	 * @var \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry
	 */
	protected $metaTagManagerRegistry;
	
	/**
	 * @var \TYPO3\CMS\Seo\MetaTag\MetaTagGenerator
	 */
	protected $metaTagGenerator;
	
	/**
	 * PageDataTransformer constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\TypoContext\TypoContext $context
	 * @param \TYPO3\CMS\Seo\Canonical\CanonicalGenerator          $canonicalGenerator
	 * @param \TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry       $metaTagManagerRegistry
	 * @param \TYPO3\CMS\Seo\MetaTag\MetaTagGenerator              $metaTagGenerator
	 */
	public function __construct(TypoContext $context, CanonicalGenerator $canonicalGenerator,
								MetaTagManagerRegistry $metaTagManagerRegistry, MetaTagGenerator $metaTagGenerator) {
		$this->context = $context;
		$this->canonicalGenerator = $canonicalGenerator;
		$this->metaTagManagerRegistry = $metaTagManagerRegistry;
		$this->metaTagGenerator = $metaTagGenerator;
	}
	
	/**
	 * @inheritDoc
	 */
	protected function transformValue($value): array {
		/** @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData $value */
		$pageObject = $value->getData();
		$result = $this->autoTransform($pageObject, ["allIncludes"]);
		$result["canonicalUrl"] = $this->getCleanCanonicalUrl();
		$result["metaTags"] = $this->getMetaTags($value);
		return $result;
	}
	
	/**
	 * Internal helper to generate the canonical url for the current page
	 * @return string
	 */
	protected function getCleanCanonicalUrl(): string {
		$requestBackup = $GLOBALS['TYPO3_REQUEST'];
		$GLOBALS['TYPO3_REQUEST'] = $this->context->getRequestAspect()->getRootRequest()->withQueryParams([]);
		$canonicalTag = $this->canonicalGenerator->generate();
		preg_match("~href=\"(.*?)\"~", $canonicalTag, $m);
		$url = $m[1];
		$url = (string)Path::makeUri($url)->withQuery(NULL);
		$GLOBALS['TYPO3_REQUEST'] = $requestBackup;
		return $url;
	}
	
	/**
	 * Internal helper to generate the meta tag definitions for the given page object
	 *
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData $value
	 *
	 * @return array
	 */
	protected function getMetaTags(PageData $value): array {
		foreach ($this->metaTagManagerRegistry->getAllManagers() as $manager) $manager->removeAllProperties();
		$this->metaTagGenerator->generate(["page" => $value->getPageInfo()]);
		$tagsString = "";
		foreach ($this->metaTagManagerRegistry->getAllManagers() as $manager) $tagsString .= $manager->renderAllProperties();
		$html = new DOMDocument("1.0", "utf-8");
		$html->loadHTML($tagsString);
		$tags = [];
		foreach ($html->getElementsByTagName("meta") as $node) {
			/** @var \DOMElement $node */
			$attributes = [];
			foreach ($node->attributes as $attr) $attributes[$attr->nodeName] = $attr->nodeValue;
			$tags[] = $attributes;
		}
		return $tags;
	}
	
}