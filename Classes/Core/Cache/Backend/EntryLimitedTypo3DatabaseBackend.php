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
 * Last modified: 2021.06.11 at 14:30
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache\Backend;


use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EntryLimitedTypo3DatabaseBackend extends Typo3DatabaseBackend
{
    /**
     * The maximum number of entries in the cache table, after this number is exceeded
     * The backend will automatically start to drop the oldest cache entries
     *
     * @var int
     */
    protected $maxEntries = 25000;
    
    /**
     * True when the count was already checked in this request
     *
     * @var bool
     */
    protected $countChecked = false;
    
    /**
     * Allows the outside world to set the max entries in the cache database table
     *
     * @param   int  $maxEntries
     */
    public function setMaxEntries(int $maxEntries): void
    {
        $this->countChecked = false;
        $this->maxEntries = $maxEntries;
    }
    
    /**
     * Returns the currently configured max entries in the cache database table
     *
     * @return int
     */
    public function getMaxEntries(): int
    {
        return $this->maxEntries;
    }
    
    /**
     * @inheritDoc
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (! $this->countChecked) {
            $this->applyMaxEntryLimit();
            $this->countChecked = true;
        }
        
        parent::set($entryIdentifier, $data, $tags, $lifetime);
    }
    
    /**
     * This failsafe makes sure that the cache table does not contain more than the configured number of cache entries.
     * This allows the extension to implement a greedy cache for requests, while keeping the DB save from overflow attacks.
     */
    protected function applyMaxEntryLimit(): void
    {
        $qb = $this->getQueryBuilder();
        
        $count = $qb->from($this->cacheTable)
                    ->selectLiteral('COUNT(*)')
                    ->execute()
                    ->fetchFirstColumn()[0] ?? 0;
        
        $maxEntries = $this->getMaxEntries();
        
        if ($count < $maxEntries) {
            return;
        }
        
        $maxEntriesToRemove = (int)min(1000, ceil($count / 2));
        
        $qb = $this->getQueryBuilder();
        $identifiersToRemove = $qb->from($this->cacheTable)
                                  ->select('identifier')
                                  ->orderBy('expires', 'ASC')
                                  ->setMaxResults($maxEntriesToRemove)
                                  ->execute()->fetchFirstColumn();
        
        if (empty($identifiersToRemove)) {
            return;
        }
        
        $identifiersToRemove = array_map(static function ($v) { return '"' . $v . '"'; }, $identifiersToRemove);
        $qb = $this->getQueryBuilder(true);
        $qb->delete($this->tagsTable)->where($qb->expr()->in('identifier', $identifiersToRemove))->execute();
        
        $qb = $this->getQueryBuilder();
        $qb->delete($this->cacheTable)->where($qb->expr()->in('identifier', $identifiersToRemove))->execute();
    }
    
    /**
     * Internal helper to retrieve the db query builder for either the cache or the tag table
     *
     * @param   bool  $tagsTable  By default the cache table builder is retrieved, if set to true
     *                            the tags tags table will be retrieved instead
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilder(bool $tagsTable = false): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
                             ->getQueryBuilderForTable($tagsTable ? $this->tagsTable : $this->cacheTable);
    }
}