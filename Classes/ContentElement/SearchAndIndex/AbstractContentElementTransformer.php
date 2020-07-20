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
 * Last modified: 2019.09.02 at 21:19
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\SearchAndIndex;


use LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement\AbstractContentElementTransformer as AbstractContentElementTransformerAlias;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\ContentElementHandler;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;


abstract class AbstractContentElementTransformer
    extends AbstractContentElementTransformerAlias
    implements ContentElementControllerInterface
{
    
    /**
     * @var ContentElementHandler
     */
    protected $contentElementHandler;
    
    /**
     * Inject the element handler using extBase
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\ContentElementHandler  $contentElementHandler
     */
    public function injectContentElementHandler(ContentElementHandler $contentElementHandler)
    {
        $this->contentElementHandler = $contentElementHandler;
    }
    
    /**
     * @inheritDoc
     */
    public function convert(array $row, IndexerContext $context): string
    {
        $config                    = $this->TypoContext()->Config()
                                          ->getTypoScriptValue(['tt_content', $row['CType']], []);
        $config['controllerClass'] = static::class;
        
        return $this->contentElementHandler->handleCustom($row, true, $config);
    }
    
    /**
     * Receives the content element context as the default controller would and should be used to generate a useful,
     * searchable content string we can add to the search index table.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     *
     * @return string
     */
    abstract protected function convertElement(ContentElementControllerContext $context): string;
    
    /**
     * @inheritDoc
     */
    public function handle(ContentElementControllerContext $context)
    {
        return $this->convertElement($context);
    }
    
    /**
     * @inheritDoc
     */
    public function handleBackend(ContentElementControllerContext $context): string
    {
        return $this->handle($context);
    }
}
