<?php
/*
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.09.25 at 22:17
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache\Scope;


use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheScopeRegistry
{
    use FrontendApiContextAwareTrait;

    /**
     * @var \LaborDigital\Typo3FrontendApi\Cache\Scope\CacheScope[]
     */
    protected $scopes = [];

    /**
     * Announces the given ttl to all currently open scopes.
     * The ttl acts as a max value for all open scopes. All scopes with a lower ttl will be unaffected
     *
     * @param   int|null  $ttl
     *
     * @return $this
     */
    public function announceTtl(?int $ttl): self
    {
        if ($ttl === null) {
            return $this;
        }

        $currentScope      = end($this->scopes);
        $currentScope->ttl = $ttl;

        foreach ($this->scopes as $scope) {
            if ($currentScope === $scope) {
                continue;
            }
            if ($scope->ttl > $ttl) {
                $scope->ttl = $ttl;
            }
        }

        return $this;
    }

    /**
     * Announces the cache enabled status of the current scope.
     * It only acts if the cache needs to be disabled, which means all currently opened scopes have to be disabled as well
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function announceIsEnabled(bool $state): self
    {
        if ($state === true) {
            return $this;
        }

        foreach ($this->scopes as $scope) {
            $scope->enabled = false;
        }

        return $this;
    }

    /**
     * Announces a the given cache tag to all currently opened scopes
     *
     * @param   string  $tag
     *
     * @return $this
     */
    public function announceTag(string $tag): self
    {
        foreach ($this->scopes as $scope) {
            $scope->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Runs the given callback inside a cache scope to collect the required caching options
     *
     * @param   callable  $callable
     *
     * @return \LaborDigital\Typo3FrontendApi\Cache\Scope\CacheScope
     */
    public function runInScope(callable $callable): CacheScope
    {
        $scope          = GeneralUtility::makeInstance(CacheScope::class);
        $scope->ttl     = $this->FrontendApiContext()->ConfigRepository()->cache()->get('defaultTtl');
        $this->scopes[] = $scope;

        try {
            $scope->result = $callable();
        } finally {
            array_pop($this->scopes);
        }

        $scope->tags = array_unique($scope->tags);

        return $scope;
    }
}
