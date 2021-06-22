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
 * Last modified: 2021.06.22 at 18:18
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement\Controller;


use LaborDigital\T3ba\ExtBase\Controller\BetterContentActionController;
use LaborDigital\T3fa\Core\ContentElement\DataView;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;

class JsonContentActionController extends BetterContentActionController
{
    use JsonContentElementControllerTrait;
    
    /**
     * Can be overwritten by controllers that don't want to use the error boundary.
     * The boundary will catch all exceptions thrown in this controller and convert them into
     * element only exceptions
     *
     * @var bool
     */
    protected $useErrorBoundary = true;
    
    /**
     * Can be overwritten by controllers that don't want to use the data view replacement.
     *
     * @var bool
     */
    protected $useDataView = true;
    
    /**
     * @inheritDoc
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->useDataView) {
            $this->defaultViewObjectName = DataView::class;
        }
        
        // static::$transfer is set when the backend preview renderer was called using the simulateRequest() method.
        // In that case we simply ignore the error boundary, so the backend preview renderer can render a nice error message.
        if (! $this->useErrorBoundary || ! empty(static::$transfer)) {
            parent::processRequest($request, $response);
        }
        
        $this->jsonErrorBoundary(function () use ($request, $response) {
            parent::processRequest($request, $response);
        });
    }
    
    
}