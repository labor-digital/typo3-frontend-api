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
 * Last modified: 2021.06.07 at 19:00
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement;


use GuzzleHttp\Psr7\Utils;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Core\ErrorHandler\Handler\ErrorLoggerTrait;
use LaborDigital\T3fa\Core\ErrorHandler\UnifiedError;
use LaborDigital\T3fa\Core\Routing\Util\ResponseFactoryTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ErrorHandler implements PublicServiceInterface
{
    use ContainerAwareTrait;
    use ResponseFactoryTrait;
    use ErrorLoggerTrait;
    
    /**
     * @var \LaborDigital\T3fa\Core\ErrorHandler\ErrorHandler
     */
    protected $coreHandler;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(
        \LaborDigital\T3fa\Core\ErrorHandler\ErrorHandler $coreHandler,
        EventDispatcherInterface $eventDispatcher,
        TypoContext $typoContext
    )
    {
        $this->coreHandler = $coreHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Tries to handle the given exception of a controller action
     *
     * @param   \Throwable                                          $e
     * @param   \TYPO3\CMS\Extbase\Mvc\Controller\ActionController  $controller
     * @param   string                                              $actionName
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Throwable $e, ActionController $controller, string $actionName): ResponseInterface
    {
        $isVerbose = $this->coreHandler->useVerboseHandler();
        $isFrontend = $this->typoContext->env()->isFrontend();
        $error = $this->makeInstance(UnifiedError::class, [$e, $this->typoContext->request()->getRootRequest()]);
        
        $this->logError($this->coreHandler->getLogger(), $error);
        
        $response = $this->callHandlerMethod(
            $controller,
            'handle' . ucfirst($actionName) . 'Exception',
            $error, $isFrontend, $isVerbose);
        
        if ($response === null) {
            $response = $this->callHandlerMethod(
                $controller,
                'handleException',
                $error, $isFrontend, $isVerbose);
        }
        
        if ($response === null) {
            if ($isVerbose) {
                $response = $this->makeVerboseError($error);
            } else {
                $response = $this->makeDefaultError();
            }
        }
        
        return $response;
    }
    
    /**
     * Tries to call the provided method on the controller class or returns null
     *
     * @param   \TYPO3\CMS\Extbase\Mvc\Controller\ActionController  $controller
     * @param   string                                              $method
     * @param   \LaborDigital\T3fa\Core\ErrorHandler\UnifiedError   $error
     * @param   bool                                                $isFrontend
     * @param   bool                                                $isVerbose
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    protected function callHandlerMethod(
        ActionController $controller,
        string $method,
        UnifiedError $error,
        bool $isFrontend,
        bool $isVerbose
    ): ?ResponseInterface
    {
        if (! is_callable([$controller, $method])) {
            return null;
        }
        
        $result = $controller->$method($error, $isFrontend, $isVerbose);
        
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        
        if (is_string($result)) {
            return $this->getResponse(500)->withBody(
                Utils::streamFor(
                    HtmlSerializer::serialize([
                        'errors' => [
                            $result,
                        ],
                    ], ['actionFailed' => true])
                )
            );
        }
        
        if (is_array($result)) {
            return $this->getResponse(500)->withBody(
                Utils::streamFor(
                    HtmlSerializer::serialize($result, ['actionFailed' => true])
                )
            );
        }
        
        return $this->makeDefaultError();
    }
    
    /**
     * Generates a default, speaking error
     *
     * @param   \LaborDigital\T3fa\Core\ErrorHandler\UnifiedError  $error
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function makeVerboseError(UnifiedError $error): ResponseInterface
    {
        return $this->getResponse(500)->withBody(
            Utils::streamFor(
                HtmlSerializer::serialize([
                    'errors' => $error->getStack(),
                ], ['actionFailed' => true])
            )
        );
    }
    
    /**
     * Generates a default non speaking error response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function makeDefaultError(): ResponseInterface
    {
        return $this->getResponse(500)->withBody(
            Utils::streamFor(
                HtmlSerializer::serialize([
                    'Error while rendering the element',
                ], ['actionFailed' => true])
            )
        );
    }
}