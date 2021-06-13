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
 * Last modified: 2021.06.10 at 13:17
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Cache\Constraint;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Database\DbService;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3ba\Tool\Tsfe\TsfeService;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use Neunerlei\Arrays\Arrays;
use Neunerlei\TinyTimy\DateTimy;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ConstraintBuilder implements PublicServiceInterface
{
    protected const DEFAULT_LIFETIME = 9999999999;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Database\DbService
     */
    protected $dbService;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Tsfe\TsfeService
     */
    protected $tsfeService;
    
    /**
     * A list of tags that were already parsed, but did not resolve into something we could actually use.
     * This keeps track of them, so we don't have to parse the tags again
     *
     * @var array
     */
    protected $ignoredTags = [];
    
    /**
     * The already resolved options for each cache tag we successfully parsed already
     *
     * @var array
     */
    protected $optionCache = [];
    
    public function __construct(DbService $dbService, EnvironmentSimulator $simulator, TsfeService $tsfeService)
    {
        $this->dbService = $dbService;
        $this->simulator = $simulator;
        $this->tsfeService = $tsfeService;
    }
    
    /**
     * Iterates the registered cache tags and tries to find "endtime" constraints for records.
     * Also applies page record specific options to the cache scope if pages_ tags are present
     *
     * @param   \LaborDigital\T3fa\Core\Cache\Scope\Scope  $scope
     */
    public function buildRecordConstraints(Scope $scope): void
    {
        $tags = $scope->getCacheTags();
        if (empty($tags)) {
            return;
        }
        
        $newLifetime = $scope->getCacheLifetime() ?? static::DEFAULT_LIFETIME;
        $newTags = [];
        
        foreach ($tags as $tag) {
            if (isset($this->ignoredTags[$tag])) {
                continue;
            }
            
            if (! isset($this->optionCache[$tag])) {
                $parsed = $this->parseTag($tag);
                if ($parsed === null) {
                    $this->ignoredTags[$tag] = true;
                    continue;
                }
                [$tableName, $id, $endTimeColumn] = $parsed;
                
                if ($tableName === 'pages') {
                    $this->optionCache[$tag]['page'] = $this->findPageCacheOptions($id);
                }
                
                $this->optionCache[$tag]['record'] = $this->findRecordCacheLifetime($tableName, $id, $endTimeColumn);
            }
            
            if (! empty($this->optionCache[$tag]['record'])) {
                $newLifetime = min($newLifetime, $this->optionCache[$tag]['record']);
            }
            
            if (! empty($this->optionCache[$tag]['page'])) {
                $pageOptions = $this->optionCache[$tag]['page'];
                if (! empty($pageOptions['tags'])) {
                    $newTags[] = $pageOptions['tags'];
                }
                if (! empty($pageOptions['lifetime'])) {
                    $newLifetime = min($newLifetime, $pageOptions['lifetime']);
                }
            }
        }
        
        if (! empty($newTags)) {
            $scope->addCacheTags(array_merge(...$newTags));
        }
        
        if ($newLifetime !== static::DEFAULT_LIFETIME) {
            $scope->setCacheLifetime($newLifetime);
        }
    }
    
    /**
     * Takes a single tag and tries to extract a table and numeric uid from it.
     * The result is either an array where [0] is the name of the table, [1] the uid, or null if the result was not valid
     * and [2] the name of the end time column
     *
     * @param   string  $tag
     *
     * @return array|null
     */
    protected function parseTag(string $tag): ?array
    {
        $parts = explode('_', $tag);
        $id = array_pop($parts);
        if (! is_numeric($id)) {
            return null;
        }
        
        $tableName = implode('_', $parts);
        $endTimeColumn = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['endtime'] ?? null;
        if (! isset($endTimeColumn)) {
            return null;
        }
        
        return [$tableName, (int)$id, $endTimeColumn];
    }
    
    /**
     * Special handle to read the page related cache lifetime and tags from the typo script frontend controller
     *
     * @param   int  $id
     *
     * @return array
     */
    protected function findPageCacheOptions(int $id): array
    {
        try {
            return $this->simulator->runWithEnvironment(['pid' => $id], function () {
                $tsfe = $this->tsfeService->getTsfe();
                
                $pageInfo = $tsfe->page;
                
                $cacheTags = [];
                if (is_array($pageInfo)) {
                    $cacheTags = array_unique(Arrays::makeFromStringList($pageInfo['cache_tags'] ?? ''));
                }
                
                return [
                    'tags' => $cacheTags,
                    'lifetime' => $this->simulateTsfeGetCacheTimeout($tsfe, $pageInfo),
                ];
            });
        } catch (Throwable $e) {
            return [];
        }
        
    }
    
    /**
     * This methods simulates the get_cache_timeout() method on the TypoScriptFrontendController
     * without applying cacheTimeOutDefault and without executing calculatePageCacheTimeout().
     * For the most part we do a better job than TYPO3 of keeping track of stuff to cache.
     * Therefore we can skip those actions.
     *
     * @param   \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController  $tsfe
     * @param   array                                                        $pageInfo
     *
     * @return int
     * @see TypoScriptFrontendController::get_cache_timeout()
     */
    protected function simulateTsfeGetCacheTimeout(TypoScriptFrontendController $tsfe, array $pageInfo): int
    {
        $lifetime = static::DEFAULT_LIFETIME;
        
        if ($pageInfo['cache_timeout']) {
            $lifetime = $pageInfo['cache_timeout'];
        }
        
        if (! empty($tsfe->config['config']['cache_clearAtMidnight'])) {
            $lifetime = $this->calculateSecondsFromEndDate(new DateTimy('tomorrow midnight'));
        }
        
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['get_cache_timeout'] ?? [] as $_funcRef) {
            $params = ['cacheTimeout' => $lifetime];
            $lifetime = GeneralUtility::callUserFunction($_funcRef, $params, $tsfe);
        }
        
        if ($lifetime === static::DEFAULT_LIFETIME) {
            return 0;
        }
        
        return (int)($lifetime ?? null);
    }
    
    /**
     * Generates the cache lifetime for a single record in a specified table
     *
     * @param   string  $tableName
     * @param   int     $id
     * @param   string  $endTimeColumn
     *
     * @return int|null
     */
    protected function findRecordCacheLifetime(string $tableName, int $id, string $endTimeColumn): ?int
    {
        $endTimeValue = $this->dbService->getQuery($tableName)
                                        ->withWhere(['uid' => $id])
                                        ->getFirst([$endTimeColumn])[$endTimeColumn] ?? null;
        if (empty($endTimeValue)) {
            return null;
        }
        
        return $this->calculateSecondsFromEndDate(new DateTimy($endTimeValue));
    }
    
    /**
     * Internal helper to calculate the seconds between now and the given date
     *
     * @param   \DateTime  $date
     *
     * @return int
     */
    protected function calculateSecondsFromEndDate(\DateTime $date): int
    {
        return abs(
            (new DateTimy('0'))
                ->add($date->diff(new DateTimy()))
                ->getTimestamp()
        );
    }
    
}