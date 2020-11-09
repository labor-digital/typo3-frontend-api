<?php
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
 * Last modified: 2019.08.28 at 11:56
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement;


use LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewRendererContext;
use LaborDigital\Typo3BetterApi\BackendPreview\BackendPreviewRendererInterface;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerInterface;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementDataPostProcessorInterface;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Repository\ContentElementRepository;
use LaborDigital\Typo3FrontendApi\ContentElement\Transformation\ContentElementDataTransformer;
use LaborDigital\Typo3FrontendApi\ContentElement\VirtualColumn\VirtualColumnUtil;
use LaborDigital\Typo3FrontendApi\Event\ContentElementAfterControllerFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementAfterWrapFilterEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementPostProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementPreProcessorEvent;
use LaborDigital\Typo3FrontendApi\Event\ContentElementSpaEvent;
use LaborDigital\Typo3FrontendApi\FrontendApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class ContentElementHandler
 *
 * @package LaborDigital\Typo3FrontendApi\ContentElements
 */
class ContentElementHandler implements SingletonInterface, BackendPreviewRendererInterface
{
    use ResponseFactoryTrait;
    use FrontendApiContextAwareTrait;

    /**
     * A list of the processed page cache config to avoid numerous lookups
     *
     * @var array
     */
    protected static $pageCacheConfigCache = [];

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
     * Will be set by the content object renderer when the user element processes our callback
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

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
    public function handleFrontend($input, array $config): string
    {
        return $this->handleCustom($this->cObj->data, true, $config, [
            'contentObjectRenderer' => $this->cObj,
            'args'                  => func_get_args(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function renderBackendPreview(BackendPreviewRendererContext $context)
    {
        $cType = Arrays::getPath($context->getRow(), ['CType']);
        if (empty($cType)) {
            throw new ContentElementException('The data for a content element did not contain a cType!');
        }

        $config = $this->FrontendApiContext()
                       ->ConfigRepository()
                       ->contentElement()
                       ->getContentElementConfig((string)$cType);

        return $this->handleCustom($context->getRow(), false, $config, [
            'context' => $context,
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
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\ContentElementException
     * @throws \LaborDigital\Typo3FrontendApi\ContentElement\SpaContentPreparedException
     */
    public function handleCustom(array $row, bool $isFrontend, array $config, array $environment = []): string
    {
        $t3faContext = $this->FrontendApiContext();

        // Allow filtering
        $this->FrontendApiContext()->EventBus()->dispatch(($e = new ContentElementPreProcessorEvent(
            (string)Arrays::getPath($row, ['CType']),
            $row,
            (string)Arrays::getPath($config, ['controllerClass']),
            $t3faContext->getRequest(),
            $isFrontend,
            $config,
            $environment
        )));
        $cType           = $e->getCType();
        $row             = $e->getRow();
        $controllerClass = $e->getControllerClass();
        $request         = $e->getRequest();
        $isFrontend      = $e->isFrontend();
        $config          = $e->getConfig();
        $environment     = $e->getEnvironment();

        // Validate cType for existence
        if (empty($cType)) {
            throw new ContentElementException('The data for a content element did not contain a cType!');
        }

        // Create the controller
        if (! class_exists($controllerClass)) {
            throw new ContentElementException('The controller class: "' . $controllerClass . '" is invalid, because it does not exist!');
        }
        $controller = $t3faContext->getInstanceOf($controllerClass);
        if (! $controller instanceof ContentElementControllerInterface) {
            throw new ContentElementException(
                'The controller class: "' . $controllerClass .
                '" is invalid, because it does not implement the required interface: "' .
                ContentElementControllerInterface::class . '"!');
        }

        // Build the context
        /** @var \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext $context */
        $context = $t3faContext->getInstanceWithoutDi(ContentElementControllerContext::class,
            [
                $cType,
                $config,
                $t3faContext->getSingletonOf(ContentElementRepository::class)->hydrateRow($row),
                $request,
                $environment,
                $this->getCssClasses($config, $row),
            ]
        );
        $context->setCacheOptionsArray($this->makeCacheConfig($config, $row));

        // Execute the controller
        $result = VirtualColumnUtil::runWithResolvedVColTca($cType,
            static function () use ($isFrontend, $controller, $context) {
                return $isFrontend ? $controller->handle($context) : $controller->handleBackend($context);
            }
        );
        $result = $t3faContext->EventBus()->dispatch(new ContentElementAfterControllerFilterEvent(
            $result, $controller, $context, $isFrontend
        ))->getResult();

        // Announce the cache configuration
        $t3faContext->CacheService()
                    ->announceTags($context->getCacheTags())
                    ->announceTtl($context->getCacheTtl())
                    ->announceIsEnabled($context->isCacheEnabled());

        // Result = string means don't do anything else...
        if (is_string($result)) {
            return $result;
        }

        // Create the element
        $element = $t3faContext->EventBus()->dispatch(new ContentElementPostProcessorEvent(
            $this->makeElementInstance($context, $result), $controller, $context, $isFrontend
        ))->getElement();

        // Break if we are running in an spa app
        if (static::$spaMode === true) {
            // @todo remove this in v10
            $t3faContext->EventBus()->dispatch(($e = new ContentElementSpaEvent(
                $element, $controller, $context, $isFrontend
            )));

            throw SpaContentPreparedException::makeInstance($element);
        }

        // Prepare the result array
        $resultArray = $element->asArray();
        foreach (['data', 'children'] as $objectField) {
            if (empty($resultArray[$objectField])) {
                $resultArray[$objectField] = [];
            }
            $resultArray[$objectField] = (object)$resultArray[$objectField];
        }

        // Post process the wrapped element data
        return $t3faContext->EventBus()->dispatch(
            new ContentElementAfterWrapFilterEvent(
                $this->buildJsonWrap($context, $resultArray), $controller, $context, $isFrontend
            )
        )->getResult();
    }

    /**
     * Generates the list of css classes of the current content element
     *
     * @param   array  $config
     * @param   array  $row
     *
     * @return array
     */
    protected function getCssClasses(array $config, array $row): array
    {
        $cssClasses = $this->FrontendApiContext()->ConfigRepository()->contentElement()->getGlobalCssClasses();

        $classFieldMap = [
            ''                            => $config['cssClasses'],
            'frame frame--'               => $row['frame_class'],
            'spacerTop spacerTop--'       => $row['space_before_class'],
            'spacerBottom spacerBottom--' => $row['space_after_class'],
        ];

        foreach ($classFieldMap as $prefix => $field) {
            if (empty($field)) {
                continue;
            }

            $cssClasses = Arrays::attach($cssClasses,
                array_map(static function ($v) use ($prefix) {
                    return $prefix . $v;
                }, Arrays::makeFromStringList($field))
            );
        }

        return array_unique($cssClasses);
    }

    /**
     * Generates the cache configuration for this content element
     *
     * @param   array  $config  The ts config for this element
     * @param   array  $row     The database row of the element
     *
     * @return array
     */
    protected function makeCacheConfig(array $config, array $row): array
    {
        $context = $this->FrontendApiContext();

        // No caching for the backend
        if (! $context->TypoContext()->Env()->isFrontend()) {
            return ['enabled' => false];
        }

        // Prepare the cache configuration
        $cacheConfig = isset($config['caching.']) && is_array($config['caching.']) ? $config['caching.'] : [];
        if (empty($cacheConfig['ttl'])) {
            $cacheConfig['ttl'] = null;
        }
        $cacheConfig['enabled'] = $cacheConfig['enabled'] === '1';
        $cacheConfig['tags']    = $cacheConfig['tags.'] ?? [];
        unset($cacheConfig['tags.']);

        // Inject page configuration
        $tsfe = $context->Tsfe()->getTsfe();
        $pid  = (int)$row['pid'];
        if (! isset(static::$pageCacheConfigCache[$pid])) {
            static::$pageCacheConfigCache[$pid] = [
                'tags' => array_unique(Arrays::makeFromStringList((string)$tsfe->page['cache_tags'])),
                'ttl'  => $tsfe->get_cache_timeout(),
            ];
        }

        $cacheConfig['tags'] = array_merge(
            $cacheConfig['tags'],
            static::$pageCacheConfigCache[$pid]['tags'],
            [
                'tt_content_' . $row['uid'],
                'page_' . $pid,
            ]
        );

        $pageCacheTtl = static::$pageCacheConfigCache[$pid]['ttl'];
        if ($cacheConfig['ttl'] > $pageCacheTtl || $cacheConfig['ttl'] === null && $pageCacheTtl > 0) {
            $cacheConfig['ttl'] = $pageCacheTtl;
        }

        return $cacheConfig;
    }

    /**
     * Generates the content element instance based on the controller context and the controller action result
     *
     * @param   ContentElementControllerContext  $controllerContext
     * @param   mixed                            $result
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
     */
    protected function makeElementInstance(ContentElementControllerContext $controllerContext, $result): ContentElement
    {
        $t3faContext = $this->FrontendApiContext();

        /** @var ContentElement $element */
        $element = $t3faContext->getInstanceWithoutDi(
            ContentElement::class,
            [
                ContentElement::TYPE_MANUAL,
                $controllerContext->getModel()->getUid(),
                $t3faContext->TypoContext()->Language()->getCurrentFrontendLanguage()->getTwoLetterIsoCode(),
            ]
        );

        $element->pid                = $controllerContext->getModel()->getPid();
        $element->useLoaderComponent = $controllerContext->useLoaderComponent();
        $element->componentType      = $controllerContext->getType();
        $element->initialState       = $this->generateInitialState($controllerContext->getInitialStateRequest());
        $element->data               = $this->generateData($controllerContext->getCType(), $result, $controllerContext);
        $element->cssClasses         = $controllerContext->getCssClasses();

        return $element;
    }

    /**
     * Makes sure the controller result is converted into an array and merged with the global data array
     *
     * @param   string                           $cType    The cType identifier for this element
     * @param   mixed                            $data     The data returned by the controller
     * @param   ContentElementControllerContext  $context  The context for the post processors
     *
     * @return array
     * @throws \LaborDigital\Typo3FrontendApi\FrontendApiException
     */
    protected function generateData(string $cType, $data, ContentElementControllerContext $context): array
    {
        // Prepare the data
        $t3faContext = $this->FrontendApiContext();
        if ($data === null) {
            $data = [];
        } else {
            $data = ContentElementDataTransformer::transformData($data, $context, $t3faContext->TransformerFactory());
        }

        // Apply the data post processors
        foreach ($t3faContext->ConfigRepository()->contentElement()->getDataPostProcessors() as $processorClass) {
            $processor = $t3faContext->getSingletonOf($processorClass);

            if (! $processor instanceof ContentElementDataPostProcessorInterface) {
                throw new FrontendApiException(
                    "The registered data post processor class $processorClass does not implement the required interface: "
                    . ContentElementDataPostProcessorInterface::class);
            }

            if (! $processor->canHandle($cType)) {
                continue;
            }

            $data = $processor->process($data, $context);
        }

        // Done
        return $data;
    }

    /**
     * Generates and caches the initial state, so we might re-use it again for other content elements
     * that reuse the same data
     *
     * @param   array|null  $request
     *
     * @return array|null
     */
    protected function generateInitialState(?array $request): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->FrontendApiContext()->CacheService()->remember(function () use ($request) {
            return $this->FrontendApiContext()->ResourceDataRepository()->findForInitialState($request)->asArray();
        }, [__FUNCTION__, $request]);
    }

    /**
     * Generates the json data html tag if the app runs as hybrid
     *
     * @param   ContentElementControllerContext  $context
     * @param   array                            $resultArray
     *
     * @return string
     * @throws \JsonException
     */
    protected function buildJsonWrap(ContentElementControllerContext $context, array $resultArray): string
    {
        if ($context->doesUseJsonWrap()) {
            $jsonWrap = $context->getJsonWrap();
            if (! is_string($jsonWrap)) {
                $jsonWrap = '<div id="{{id}}" data-typo-frontend-api-content-element="{{definitionAttr}}">' .
                            ($this->FrontendApiContext()->TypoContext()->Env()->isDev() ?
                                PHP_EOL
                                . '<script type="application/json" data-comment="You only see this in development mode!">'
                                . PHP_EOL . '{{definitionPretty}}' . PHP_EOL . '</script>' . PHP_EOL : '') . '</div>';
            }
            $result = $jsonWrap;
            if (stripos($result, '{{definitionPretty}}') !== false) {
                $result = str_replace('{{definitionPretty}}',
                    json_encode($resultArray, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), $result);
            }
            if (stripos($result, '{{definitionAttr}}') !== false) {
                $result = str_replace('{{definitionAttr}}', htmlentities(json_encode($resultArray, JSON_THROW_ON_ERROR)), $result);
            }
            if (stripos($result, '{{id}}') !== false) {
                $result = str_replace(
                    '{{id}}',
                    'content-element-' . $context->getModel()->getUid() . '-' . Inflector::toFile($context->getType()),
                    $result
                );
            }
        } else {
            $result = json_encode($resultArray, JSON_THROW_ON_ERROR);
        }

        return $result;
    }
}
