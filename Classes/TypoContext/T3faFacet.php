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
 * Last modified: 2021.05.17 at 19:45
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\TypoContext;


use LaborDigital\T3ba\Tool\TypoContext\FacetInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use Neunerlei\Arrays\Arrays;

class T3faFacet implements FacetInterface
{
    use SiteConfigAwareTrait;
    
    public function __construct(TypoContext $context)
    {
        $this->context = $context;
        $this->registerConfig('t3fa');
    }
    
    /**
     * @inheritDoc
     */
    public static function getIdentifier(): string
    {
        return 't3fa';
    }
    
    /**
     * Similar to ConfigFacet::getConfigValue(), but limited to the currently selected frontend api site.
     *
     * @param   string      $key       Either a simple key or a colon separated path to find the value at
     * @param   null|mixed  $fallback  Returned if the $key was not found in the state
     *
     * @return mixed|null
     * @see \LaborDigital\T3ba\TypoContext\ConfigFacet::getConfigValue()
     */
    public function getConfigValue(string $key, $fallback = null)
    {
        return Arrays::getPath($this->getSiteConfig(), $key, $fallback);
    }
    
}