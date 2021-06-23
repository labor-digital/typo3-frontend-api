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
 * Last modified: 2021.06.23 at 12:29
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement\Controller;


use HttpException;
use LaborDigital\T3ba\ExtBase\Controller\ControllerUtil;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\ContentElement\ErrorHandler;
use LaborDigital\T3fa\Core\ContentElement\Response\JsonResponse;
use LaborDigital\T3fa\Core\ContentElement\Response\ResponseFactory;
use LaborDigital\T3fa\Core\Link\ApiLink;
use League\Route\Http\Exception;
use Throwable;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Service\ExtensionService;

trait JsonContentElementControllerTrait
{
    /**
     * Wraps the given callback and automatically handles all throw exceptions inside it using the content element
     * error handler. This includes the execution of optional handleException() and handle$ActionNameException() methods
     * as well as emitting an event for the world to know of our issues.
     *
     * @param   callable  $callback
     *
     * @return mixed|null Returns either the result of $callback or null if the execution failed
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    protected function jsonErrorBoundary(callable $callback)
    {
        ControllerUtil::requireActionController($this);
        
        try {
            return $callback();
        } catch (StopActionException | HttpException | Exception | StatusException $e) {
            throw $e;
        } catch (Throwable $e) {
            /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController $this */
            $response = TypoContext::getInstance()
                                   ->di()->getService(ErrorHandler::class)
                                   ->handle($e, $this, $this->request->getControllerActionName());
            
            // TYPO3 v11 will introduce responses for extbase controller
            // This is a temporary fix until then.
            $this->response->setContent($response->getBody());
        }
        
        return null;
    }
    
    /**
     * Factory to create a new json response object for this content element.
     *
     * @return \LaborDigital\T3fa\Core\ContentElement\Response\JsonResponse
     */
    protected function getJsonResponse(): JsonResponse
    {
        ControllerUtil::requireActionController($this);
        
        /** @noinspection PhpParamsInspection */
        return TypoContext::getInstance()->di()
                          ->getService(ResponseFactory::class)
                          ->make($this->request, $this->view, JsonControllerUtil::resolveRow($this));
    }
    
    /**
     * Helper to generate the information for RESTful requests on other actions.
     * You would want to use it if your element should have a special action on POST, PUT or DELETE requests.
     *
     * It creates an array containing two values:
     * - "endpoint" is the unique url your frontend implementation can submit its data to
     * - "namespace" is the namespace in which the body data, e.g. your form field name should be prefixed with.
     * This is required for extbase to map the body data to the actual content element.
     *
     * @param   string|null  $actionName  Can be used to set the action name that should handle the request.
     *                                    If omitted the current action name will be used.
     *
     * @return array
     */
    protected function getFormInfo(?string $actionName = null): array
    {
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController $this */
        ControllerUtil::requireActionController($this);
        
        if (! $actionName) {
            $actionName = $this->request->getControllerActionName();
        }
        
        $row = JsonControllerUtil::resolveRow($this);
        $di = TypoContext::getInstance()->di();
        
        $link = $di->makeInstance(ApiLink::class)
                   ->withRouteName('resource-contentElement-single')
                   ->withRouteArguments(['id' => $row['uid']])
                   ->withSlugLinkBuilder(
                       $di->getService(LinkService::class)
                          ->getLink()
                          ->withRequest($this->request)
                          ->withControllerAction($actionName)
                   );
        
        $namespace = $this->getService(ExtensionService::class)->getPluginNamespace(
            $this->request->getControllerExtensionName(), $this->request->getPluginName());
        
        return [
            'endpoint' => $link,
            'namespace' => $namespace,
        ];
    }
}