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
 * Last modified: 2021.06.22 at 13:17
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Transformer;


use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use LaborDigital\T3fa\Core\Link\ApiLink;
use LaborDigital\T3fa\Core\Resource\Transformer\TransformerInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class TypoLinkTransformer implements TransformerInterface
{
    use T3faCacheAwareTrait;
    
    /**
     * @inheritDoc
     */
    public function transform($value)
    {
        if ($value instanceof ApiLink) {
            return $value->build();
        }
        
        if ($value instanceof Link) {
            $this->runInCacheScope(static function (Scope $scope) use ($value) {
                $scope->addCacheTags($value->getArgs());
                $scope->addCacheTag($value->getPid() !== null ? 'pages_' . $value->getPid() : null);
            });
            
            return $value->build();
        }
        
        if ($value instanceof UriInterface) {
            return (string)$value;
        }
        
        if ($value instanceof UriBuilder) {
            $this->runInCacheScope(static function (Scope $scope) use ($value) {
                $scope->addCacheTags($value->getArguments());
                $scope->addCacheTag($value->getTargetPageUid() !== null ? 'pages_' . $value->getTargetPageUid() : null);
            });
            
            return $value->buildFrontendUri();
        }
        
        // Not found
        return null;
    }
    
}