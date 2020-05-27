<?php
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
 * Last modified: 2019.08.28 at 11:56
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement;


use GuzzleHttp\Psr7\ServerRequest;
use LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewRendererContext;
use LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewRendererInterface;
use LaborDigital\Typo3BetterApi\Container\CommonServiceDependencyTrait;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementDataPostProcessorInterface;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Repository\ContentElementRepository;
use LaborDigital\Typo3FrontendApi\ContentElement\Transformation\ContentElementDataTransformer;
use LaborDigital\Typo3FrontendApi\Event\ContentElementAfterControllerFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementAfterWrapFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementPreProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementSpaEvent;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use LaborDigital\Typo3FrontendApi\FrontendApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement;
use LaborDigital\Typo3FrontendApi\JsonApi\Retrieval\ResourceDataRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerFactory;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class ContentElementHandler
 *
 * @package LaborDigital\Typo3FrontendApi\ContentElements
 */
class ContentElementHandler implements SingletonInterface, BackendPreviewRendererInterface
{
    use ResponseFactoryTrait;
    use CommonServiceDependencyTrait;
    
    /**
     * Will be set by the content object renderer when the user element processes our callback
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;
    
    /**
     * This is set by the page layout transformer to tell the handler if the contents should
     * be rendered in Single Page App mode -> Meaning the real result should be a json object.
     * In that case the result will not be wrapped by any kind of html.
     *
     * In SPA mode we will not wrap the element, but emit the EVENT_CONTENT_ELEMENT_SPA, containing
     * the raw "document" array for the world to receive. If the "kill" argument of said event was set to true
     * will will also throw a new SpaException to avoid all unnecessary overhead.
     *
     * Note, that in SPA mode the EVENT_CONTENT_ELEMENT_AFTER_WRAP event will not be emitted!
     *
     * @var bool
     */
    public static $spaMode = false;
    
    /**
     * Holds the instance of the server request as cache property
     *
     * @var ServerRequestInterface
     */
    protected $request;
    
    /**
     * The list of data post processor instances
     *
     * @var ContentElementDataPostProcessorInterface[]
     */
    protected $dataPostProcessors = [];
    
    /**
     * This method is used as userFunc in the registered user content object in the typoScript.
     *
     * @param          $input
     * @param   array  $config
     *
     * @return string
     */
    public function handleFrontend($input, array $config)
    {
        return $this->handleCustom($this->cObj->data, true, $config, [
            "contentObjectRenderer" => $this->cObj,
            "args"                  => func_get_args(),
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function renderBackendPreview(BackendPreviewRendererContext $context)
    {
        $cType = Arrays::getPath($context->getRow(), ["CType"]);
        if (empty($cType)) {
            throw new ContentElementException("The data for a content element did not contain a cType!");
        }
        $config = $this->ConfigRepository()->contentElement()->getContentElementConfig($cType);
        
        return $this->handleCustom($context->getRow(), false, $config, [
            "context" => $context,
        ]);
    }
    
    /**
     * The internal handler to prepare and execute the controller instance for both front- and backend actions.
     *
     * @param   array  $row          The raw database row of the tt_content record to render
     * @param   bool   $isFrontend   True if the frontend action should be called, false if not
     * @param   array  $config       The typoScript configuration array for this element
     * @param   array  $environment  Additional data that will be passed to the controller context object
     *
     * @return string
     * @throws \HttpException
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\SpaContentPreparedException
     * @throws \TYPO3\CMS\Core\Error\Http\StatusException
     */
    public function handleCustom(array $row, bool $isFrontend, array $config, array $environment = []): string
    {
        // Prepare the controller
        $request = $this->getOrMakeServerRequest();
        $cType   = Arrays::getPath($row, ["CType"]);
        if (empty($cType)) {
            throw new ContentElementException("The data for a content element did not contain a cType!");
        }
        $controllerClass = Arrays::getPath($config, ["controllerClass"]);
        if (empty($controllerClass)) {
            throw new ContentElementException("The configuration for content element $cType did not contain a controller class!");
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new ContentElementPreProcessorEvent(
            $cType, $row, $controllerClass, $request, $isFrontend, $config, $environment
        )));
        $cType           = $e->getCType();
        $row             = $e->getRow();
        $controllerClass = $e->getControllerClass();
        $request         = $e->getRequest();
        $isFrontend      = $e->isFrontend();
        $config          = $e->getConfig();
        $environment     = $e->getEnvironment();
        
        /** @var \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface $controller */
        $controller = $this->getInstanceOf($controllerClass);
        
        // Load the model
        $model = $this->getService(ContentElementRepository::class)->hydrateRow($row);
        
        // Prepare css classes
        $cssClasses = $this->ConfigRepository()->contentElement()->getGlobalCssClasses();
        foreach (
            [
                ""                            => $config["cssClasses"],
                "frame frame--"               => $row["frame_class"],
                "spacerTop spacerTop--"       => $row["space_before_class"],
                "spacerBottom spacerBottom--" => $row["space_after_class"],
            ] as $prefix => $field
        ) {
            if (! empty($field)) {
                $cssClasses = Arrays::attach($cssClasses,
                    array_map(function ($v) use ($prefix) {
                        return $prefix . $v;
                    }, Arrays::makeFromStringList($field))
                );
            }
        }
        $cssClasses = array_unique($cssClasses);
        
        // Build the context
        /** @var ContentElementControllerContext $context */
        $context = $this->getInstanceOf(ContentElementControllerContext::class, [
            $cType,
            $config,
            $model,
            $request,
            $environment,
            $cssClasses,
        ]);
        
        // Remap the virtual TCA columns
        $result = $this->handleVirtualColumnTcaRewrite($cType, function () use ($isFrontend, $controller, $context) {
            // Run the controller
            return $isFrontend ? $controller->handle($context) : $controller->handleBackend($context);
        });
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new ContentElementAfterControllerFilterEvent(
            $result, $controller, $context, $isFrontend
        )));
        $result = $e->getResult();
        
        // Result = string means don't do anything else...
        if (is_string($result)) {
            return $result;
        }
        
        // Create the element
        $element                     = $this->getInstanceOf(ContentElement::class,
            [ContentElement::TYPE_MANUAL, $model->getUid()]);
        $element->useLoaderComponent = $context->useLoaderComponent();
        $element->componentType      = $context->getType();
        if ($context->getInitialStateRequest() !== null) {
            $element->initialState = $this->getService(ResourceDataRepository::class)
                                          ->findForInitialState($context->getInitialStateRequest());
        }
        $element->data         = $this->generateData($cType, $result, $context);
        $element->languageCode = $this->TypoContext()->Language()->getCurrentFrontendLanguage()
                                      ->getTwoLetterIsoCode();
        $element->cssClasses   = $context->getCssClasses();
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new ContentElementPostProcessorEvent(
            $element, $controller, $context, $isFrontend
        )));
        $element = $e->getElement();
        
        // Special SPA handling
        if (static::$spaMode === true) {
            $this->EventBus()->dispatch(($e = new ContentElementSpaEvent(
                $element, $controller, $context, $isFrontend
            )));
            if ($e->isKillHandler()) {
                throw new SpaContentPreparedException($this->getResponse());
            }
        }
        
        // Prepare the result array
        $resultArray = $element->asArray();
        foreach (["data", "children"] as $objectField) {
            if (empty($resultArray[$objectField])) {
                $resultArray[$objectField] = [];
            }
            $resultArray[$objectField] = (object)$resultArray[$objectField];
        }
        
        // Wrap the json
        if ($context->doesUseJsonWrap()) {
            $jsonWrap = $context->getJsonWrap();
            if (! is_string($jsonWrap)) {
                $jsonWrap = "<div id=\"{{id}}\" data-typo-frontend-api-content-element=\"{{definitionAttr}}\">" .
                            ($this->TypoContext()->Env()->isDev() ?
                                PHP_EOL
                                . "<script type=\"application/json\" data-comment=\"You only see this in development mode!\">"
                                .
                                PHP_EOL . "{{definitionPretty}}" . PHP_EOL . "</script>" . PHP_EOL : "") . "</div>";
            }
            $result = $jsonWrap;
            if (stripos($result, "{{definitionPretty}}") !== false) {
                $result = str_replace("{{definitionPretty}}",
                    json_encode($resultArray, JSON_PRETTY_PRINT), $result);
            }
            if (stripos($result, "{{definitionAttr}}") !== false) {
                $result = str_replace("{{definitionAttr}}",
                    htmlentities(json_encode($resultArray)), $result);
            }
            if (stripos($result, "{{id}}") !== false) {
                $result = str_replace("{{id}}",
                    "content-element-" . $model->getUid() . "-" . Inflector::toFile($context->getType()), $result);
            }
        } else {
            $result = json_encode($resultArray);
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new ContentElementAfterWrapFilterEvent(
            $result, $controller, $context, $isFrontend
        )));
        
        return $e->getResult();
    }
    
    /**
     * Internal helper to create and serve a server request instance
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function getOrMakeServerRequest(): ServerRequestInterface
    {
        if (isset($this->request)) {
            return $this->request;
        }
        $rootRequest = $this->TypoContext()->Request()->getRootRequest();
        if (empty($rootRequest)) {
            $rootRequest = ServerRequest::fromGlobals();
        }
        
        return $this->request = $rootRequest;
    }
    
    /**
     * Makes sure the controller result is converted into an array and merged with the global data array
     *
     * @param   string                           $cType    The cType identifier for this element
     * @param   mixed                            $data     The data returned by the controller
     * @param   ContentElementControllerContext  $context  The context for the post processors
     *
     * @return array
     */
    protected function generateData(string $cType, $data, ContentElementControllerContext $context): array
    {
        // Prepare the data
        if (is_null($data)) {
            $data = [];
        } else {
            $data = ContentElementDataTransformer::transformData(
                $data, $context, $this->getService(TransformerFactory::class));
        }
        
        // Apply the data post processors
        foreach ($this->getDataPostProcessorStack() as $processor) {
            if (! $processor->canHandle($cType)) {
                continue;
            }
            $data = $processor->process($data, $context);
        }
        
        // Done
        return $data;
    }
    
    /**
     * Returns the registered instances of the data post processors to apply
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementDataPostProcessorInterface[]
     * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
     */
    protected function getDataPostProcessorStack(): array
    {
        if (! is_null($this->dataPostProcessors)) {
            return $this->dataPostProcessors;
        }
        $this->dataPostProcessors = [];
        foreach ($this->ConfigRepository()->contentElement()->getDataPostProcessors() as $postProcessor) {
            $i = $this->getInstanceOf($postProcessor);
            if (! $i instanceof ContentElementDataPostProcessorInterface) {
                throw new FrontendApiException("The registered data post processor class $postProcessor does not implement the required interface: "
                                               . ContentElementDataPostProcessorInterface::class);
            }
            $this->dataPostProcessors[] = $i;
        }
        
        return $this->dataPostProcessors;
    }
    
    /**
     * For the most part the virtual columns work out of the box,
     * however when resolving file references in the backend there are issues where typo3 expects
     * the "virtual" column names to exist in the tca. To provide a polyfill in the content element
     * controller we rewrite the vCol_ definitions the their internal column name so typo3 can resolve the
     * virtual columns without problems.
     *
     * @param   string    $cType
     * @param   callable  $wrapper
     *
     * @return mixed
     * @throws \Throwable
     */
    protected function handleVirtualColumnTcaRewrite(string $cType, callable $wrapper)
    {
        // Store the original tca and prepare the reverting function
        $originalTca = serialize(Arrays::getPath($GLOBALS, ["TCA", "tt_content", "columns"], []));
        $revert      = function () use ($originalTca) {
            $GLOBALS["TCA"]["tt_content"]["columns"] = unserialize($originalTca);
        };
        
        try {
            // Remap the existing tca using the content element map
            $columnMap = $this->ConfigRepository()->contentElement()->getVirtualColumnsFor($cType);
            foreach ($columnMap as $target => $real) {
                $config                                           = Arrays::getPath($GLOBALS,
                    ["TCA", "tt_content", "columns", $real], []);
                $GLOBALS["TCA"]["tt_content"]["columns"][$target] = $config;
            }
            
            // Run the real code
            $result = call_user_func($wrapper);
            $revert();
            
            return $result;
        } catch (Throwable $e) {
            $revert();
            throw $e;
        }
    }
    
    /**
     * Returns the instance of the API configuration repository
     *
     * @return \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected function ConfigRepository(): FrontendApiConfigRepository
    {
        return $this->getService(FrontendApiConfigRepository::class);
    }
}
