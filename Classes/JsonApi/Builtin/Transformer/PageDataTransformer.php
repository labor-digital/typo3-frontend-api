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
 * Last modified: 2019.09.20 at 18:44
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer;


use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\ArrayBasedCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;

class PageDataTransformer extends AbstractResourceTransformer
{
    /**
     * @inheritDoc
     */
    protected function transformValue($value): array
    {
        /** @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\PageData $value */
        $context = $this->FrontendApiContext();

        return $context->CacheService()->remember(
            function () use ($value) {
                $result                 = $this->autoTransform($value->getData(), ['allIncludes']);
                $result['metaTags']     = $value->getMetaTags();
                $result['hrefLang']     = $value->getHrefLangUrls();
                $result['canonicalUrl'] = $value->getCanonicalUrl();

                return $result;
            },
            [
                'tags'         => ['page_' . $value->getId(), 'pages_' . $value->getId()],
                'keyGenerator' => $context->getInstanceWithoutDi(ArrayBasedCacheKeyGenerator::class, [
                    [__CLASS__, $value->getId(), $value->getLanguageCode()],
                ]),
            ]
        );
    }


}
