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
 * Last modified: 2021.06.22 at 21:25
 */

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
 * Last modified: 2019.08.26 at 18:48
 */

namespace LaborDigital\T3fa\Api\Route;


use LaborDigital\T3ba\Tool\Database\DbService;
use LaborDigital\T3fa\Core\Routing\Controller\AbstractRouteController;
use Psr\Http\Message\ResponseInterface;

class UpController extends AbstractRouteController
{
    /**
     * @var \LaborDigital\T3ba\Tool\Database\DbService
     */
    protected $dbService;
    
    public function __construct(DbService $dbService)
    {
        $this->dbService = $dbService;
    }
    
    /**
     * Renders a simple OK if the system is up and running.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function upAction(): ResponseInterface
    {
        // Execute a simple db query to make sure the database can be accessed
        $this->dbService->getQuery('pages')->getCount();
        
        return $this->getJsonResponse([
            'status' => 'OK',
            'timestamp' => time(),
        ]);
    }
}