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
 * Last modified: 2021.05.21 at 19:10
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\Resource;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceConfigurator;

interface ResourceInterface extends PublicServiceInterface
{
    /**
     * Allows this resource to tell the system how it should be handled correctly.
     *
     * @param   \LaborDigital\T3fa\ExtConfigHandler\ApiSite\Resource\ResourceConfigurator  $configurator
     * @param   \LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext                   $context
     */
    public static function configure(ResourceConfigurator $configurator, SiteConfigContext $context): void;
    
    /**
     * Should handle the lookup of a single resource from the repository and return the result.
     * The result may be an array or any DB result / query object
     * The transformation will be handled automatically based on the configuration.
     *
     * @param   string|int       $id  The id of the resource to look up
     * @param   ResourceContext  $context
     *
     * @return mixed
     */
    public function findSingle($id, ResourceContext $context);
    
    /**
     * Should handle the lookup of a resource collection from the repository and return the result.
     * The result may be an array, any iterable (including ObjectStorage) or any DB result / query object
     * The transformation will be handled automatically based on the configuration.
     *
     * @param   ResourceQuery              $resourceQuery
     * @param   ResourceCollectionContext  $context
     *
     * @return mixed
     */
    public function findCollection(ResourceQuery $resourceQuery, ResourceCollectionContext $context);
}