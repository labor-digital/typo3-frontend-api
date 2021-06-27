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
 * Last modified: 2021.06.25 at 13:24
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource\Transformer\AutoMagic;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Tool\Database\BetterQuery\AbstractBetterQuery;
use LaborDigital\T3ba\Tool\Database\BetterQuery\BetterQueryException;
use LaborDigital\T3ba\Tool\Database\BetterQuery\ExtBase\ExtBaseBetterQuery;
use LaborDigital\T3ba\Tool\Database\BetterQuery\Standalone\RelatedRecordRow;
use LaborDigital\T3ba\Tool\OddsAndEnds\LazyLoadingUtil;
use stdClass;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class AutoTransformUtil implements NoDiInterface
{
    /**
     * Optional extension point to register additional converters I did not include already.
     * I would suggest to register your converters in the ExtLocalConfLoadedEvent.
     * The callable will receive the value and should return the value no matter if converted or not.
     *
     * @var callable[]
     */
    public static $additionalConverters = [];
    
    /**
     * Helper to retrieve real values for lazy loading proxies, db results and queries and a like
     *
     * @param   mixed  $value  The value that should be unified
     *
     * @return mixed|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public static function unifyValue($value)
    {
        if (is_object($value)) {
            $value = LazyLoadingUtil::getRealValue($value);
            $value = static::unifyDbData($value);
            
            if ($value instanceof stdClass) {
                $value = (array)$value;
            }
        }
        
        foreach (static::$additionalConverters as $converter) {
            $value = $converter($value);
        }
        
        return $value;
    }
    
    /**
     * Internal helper that makes sure that all the different database objects get unified into a query response
     * interface
     *
     * @param $value
     *
     * @return mixed|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    protected static function unifyDbData($value)
    {
        if ($value instanceof ExtBaseBetterQuery) {
            return $value->getAll();
        }
        
        if ($value instanceof AbstractBetterQuery) {
            $value = $value->getQueryBuilder();
        }
        
        if ($value instanceof QueryInterface) {
            return $value->execute();
        }
        
        if ($value instanceof QueryBuilder || $value instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            return $value->execute()->fetchAllAssociative();
        }
        
        if ($value instanceof RelatedRecordRow) {
            try {
                $value = $value->getModel();
            } catch (BetterQueryException $e) {
                $value = $value->getRow();
            }
        }
        
        return $value;
    }
    
}