<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.06.09 at 18:45
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement\Response;


use GuzzleHttp\Psr7\Utils;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\ContentElement\HtmlSerializer;
use Neunerlei\TinyTimy\DateTimy;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Extbase\Mvc\View\AbstractView;

class BodyBuilder implements PublicServiceInterface
{
    /**
     * @var \LaborDigital\T3fa\Core\ContentElement\Response\DataResolver
     */
    protected $dataResolver;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(
        DataResolver $dataResolver,
        TypoContext $typoContext
    )
    {
        $this->dataResolver = $dataResolver;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Retrieves
     *
     * @param   \LaborDigital\T3fa\Core\ContentElement\Response\JsonResponse  $response
     * @param   \TYPO3\CMS\Extbase\Mvc\View\AbstractView                      $view
     * @param   array                                                         $row
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function build(JsonResponse $response, AbstractView $view, array $row): StreamInterface
    {
        $meta = [
            'cache' => $response->getCacheOptions(),
        ];
        
        $attributes = [
            'pid' => (int)($row['pid'] ?? 1),
            'id' => (int)($row['uid'] ?? 1),
            'cssClasses' => $response->getCssClasses(),
            'componentType' => implode('/', array_filter([
                $response->getTypeNs(),
                $response->getType(),
                $response->getSubType(),
            ])),
            'children' => [],
            'data' => $this->dataResolver->generateData(
                $response->getData(false), $view, $response->getDataTransformerOptions(null)),
            'initialState' => $this->dataResolver->generateInitialState($response->getInitialStateQuery()),
            'meta' => [
                'language' => $this->typoContext->language()->getCurrentFrontendLanguage()->getTwoLetterIsoCode(),
                'generated' => (new DateTimy())->formatJs(),
            ],
        ];
        
        return Utils::streamFor(
            HtmlSerializer::serialize($attributes, $meta)
        );
    }
    
}