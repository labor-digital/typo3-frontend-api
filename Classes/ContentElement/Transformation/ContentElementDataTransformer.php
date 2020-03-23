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
 * Last modified: 2019.11.27 at 23:35
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Transformation;


use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\Transformer;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;
use Neunerlei\Options\Options;

class ContentElementDataTransformer extends AbstractResourceTransformer {
	/**
	 * @inheritDoc
	 */
	protected function transformValue($value): array {
		// noop
		return [];
	}
	
	/**
	 * This method serves as proxy so that we can access the autoTransform method of child transformers
	 * It is only used to transform content element data objects
	 *
	 * @param                                                                                          $data
	 * @param \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext $context
	 * @param \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory                 $factory
	 *
	 * @return array
	 */
	public static function transformData($data, ContentElementControllerContext $context, TransformerFactory $factory): array {
		$options = Options::make($context->getDataTransformerOptions(), [
			"allIncludes" => [
				"default" => FALSE,
				"type"    => "bool",
			],
		]);
		if ($options["allIncludes"]) {
			// Do the heavy lifting and load all includes...
			$transformer = $factory->getTransformer()->getConcreteTransformer($data);
			$transformerConfig = $transformer->getTransformerConfig();
			if ($transformerConfig->transformerClass === Transformer::class)
				return $transformer->autoTransform($data, $options);
			else $result = $transformer->transform($data);
			foreach ($transformerConfig->includes as $k => $include)
				$result[$k] = $transformer->autoTransform($include["getter"]($data), $options);
			return $result;
		}
		return $factory->getTransformer()->transform($data);
	}
}