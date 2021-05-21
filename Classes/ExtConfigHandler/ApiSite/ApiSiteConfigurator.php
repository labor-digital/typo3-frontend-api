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
 * Last modified: 2021.05.19 at 23:21
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\ApiSite;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\PageConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Transformer\TransformerConfigurator;

class ApiSiteConfigurator implements NoDiInterface
{
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Transformer\TransformerConfigurator
     */
    protected $transformerCollector;
    
    /**
     * @var \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\PageConfigurator
     */
    protected $pageConfigurator;
    
    public function __construct(
        TransformerConfigurator $transformerCollector,
        PageConfigurator $pageConfigurator
    )
    {
        $this->transformerCollector = $transformerCollector;
        $this->pageConfigurator = $pageConfigurator;
    }
    
    /**
     * Access to the list of globally registered transformers for this site
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Transformer\TransformerConfigurator
     */
    public function transformer(): TransformerConfigurator
    {
        return $this->transformerCollector;
    }
    
    /**
     * Access to the list of page related resource options
     *
     * @return \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Page\PageConfigurator
     */
    public function page(): PageConfigurator
    {
        return $this->pageConfigurator;
    }
}