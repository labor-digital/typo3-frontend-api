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
 * Last modified: 2019.08.29 at 07:16
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Controller;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;

class ContentElementControllerContext
{

    /**
     * The c-type that is used as unique key for the content element
     *
     * @var string
     */
    protected $cType;

    /**
     * The identifier for this content element. Will be set to ExtKey/ElementKey.
     * It should be used by the frontend framework to determine which component can handle this data
     *
     * @var string
     */
    protected $type;

    /**
     * Holds the request for the initial state (data) that should be resolved on the server side and then included in
     * the definition array.
     *
     * @var array|null
     */
    protected $initialStateRequest;

    /**
     * The raw configuration array that was given to this content element handler
     *
     * @var array
     */
    protected $config;

    /**
     * The instance of the data model for this content element
     *
     * @var AbstractContentElementModel|mixed
     */
    protected $data;

    /**
     * The server request that lead to this content element
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * If this is true the element will have the json wrap applied to it.
     * If this is false the json will be rendered as raw string.
     *
     * @var bool
     */
    protected $useJsonWrap = true;

    /**
     * This can be used to overwrite the jsonWrap html template that is applied if $useJsonWrap is set to true.
     * Use the marker {{document}} to mark the spot where the actual json string should be inserted into the template.
     * If this is null the default json wrap will be used.
     *
     * @var string|null
     */
    protected $jsonWrap;

    /**
     * Contains additional data that was passed to the content element handler from the environment.
     * This can be the content object renderer in the frontend, or the backend preview rendering context in the backend.
     *
     * @var array
     */
    protected $environment = [];

    /**
     * The list of css classes that will be provided for this content element
     *
     * @var array
     */
    protected $cssClasses = [];

    /**
     * The given options for the data transformer
     *
     * @var array
     */
    protected $dataTransformerOptions = [];

    /**
     * By default all components will show the "loader" static component when you are
     * using the typo-frontend-api. If you set this flag to false, the loader will not
     * be shown. The content element will simply appear when it is done loading
     *
     * @var bool
     */
    protected $useLoaderComponent = true;

    /**
     * ContentElementControllerContext constructor.
     *
     * @param   string                       $cType        The configured cType for this content element
     * @param   array                        $config       The given typoScript configuration array
     * @param   AbstractContentElementModel  $model        The prepared model of data for ths controller
     * @param   ServerRequestInterface       $request      The request that was used to execute this content element
     * @param   array                        $environment  Additional data that was passed from the environment
     * @param   array                        $cssClasses   The css classes that should be provided to this content element
     */
    public function __construct(
        string $cType,
        array $config,
        AbstractContentElementModel $model,
        ServerRequestInterface $request,
        array $environment,
        array $cssClasses
    ) {
        $this->config      = $config;
        $this->data        = $model;
        $this->request     = $request;
        $this->cType       = $cType;
        $type              = explode('_', $cType);
        $this->type        = Arrays::getPath($config, ['type'], $type[0] . '/' . $type[1]);
        $this->environment = $environment;
        $this->cssClasses  = $cssClasses;
    }

    /**
     * Returns the instance of the data model for this content element
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\AbstractContentElementModel|\LaborDigital\Typo3FrontendApi\ContentElement\Domain\Model\DefaultContentElementModel
     */
    public function getData(): AbstractContentElementModel
    {
        return $this->data;
    }

    /**
     * Returns the raw configuration array that was given by typoScript
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Returns the server request instance that was used to call this content element
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Returns the c-type that is used as unique key for the content element
     *
     * @return string
     */
    public function getCType(): string
    {
        return $this->cType;
    }

    /**
     * Returns the identifier for this content element.
     * It should be used by the frontend framework to determine which component can handle this data.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Can be used to set the identifier for this content element.
     * It should be used by the frontend framework to determine which component can handle this data.
     *
     * @param   string  $type
     *
     * @return ContentElementControllerContext
     */
    public function setType(string $type): ContentElementControllerContext
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the API uri that should be fetched and included into the rendered html code. It serves as initial state and
     * alleviate the need to send a initial ajax request when the content element is rendered by a frontend framework.
     *
     * @param   string  $initialStateUri
     *
     * @return ContentElementControllerContext
     */
    public function setInitialStateUri(string $initialStateUri): ContentElementControllerContext
    {
        $this->initialStateRequest = [
            'type' => 'uri',
            'uri'  => $initialStateUri,
        ];

        return $this;
    }

    /**
     * Similar to setInitialStateUri() in the fact that it can be used to configure the API uri that should be fetched
     * and included into the rendered html code. But instead of a static url you can define a query object that is then
     * converted into an api request. The query is exactly the same as in the (at)labor/frontend-api js counterpart. If
     * a query is configured it will also be embedded in the content elements data array.
     *
     * If you want to select a single element from the request, provide a query attribute called "id" to your
     * $query array. In that case it will automatically be prepended to your query uri fragment
     *
     * @param   string       $resourceType
     * @param   array        $query
     * @param   string|null  $additionalUriFragment
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
     */
    public function setInitialStateQuery(string $resourceType, array $query = [], ?string $additionalUriFragment = null): ContentElementControllerContext
    {
        // Check if an id was given
        if (isset($query['id'])) {
            $additionalUriFragment = rtrim($query['id'] . '/' . ltrim($additionalUriFragment, '/ '), '/ ');
            unset($query['id']);
        }

        // Set the request
        $this->initialStateRequest = [
            'type'         => 'query',
            'resourceType' => $resourceType,
            'query'        => $query,
            'uri'          => $additionalUriFragment,
        ];

        return $this;
    }

    /**
     * Returns the currently configured initial request array or null if there is none.
     *
     * @return array|null
     */
    public function getInitialStateRequest(): ?array
    {
        return $this->initialStateRequest;
    }

    /**
     * Sets the RAW initial state request array. This is for low level changes only!
     *
     * @param   array|null  $request
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
     */
    public function setInitialStateRequest(?array $request): ContentElementControllerContext
    {
        $this->initialStateRequest = $request;

        return $this;
    }

    /**
     * Returns true if the json html wrap should be applied, false if not
     *
     * @return bool
     */
    public function doesUseJsonWrap(): bool
    {
        return $this->useJsonWrap;
    }

    /**
     * If this is set to true the element will have the json wrap applied to it.
     * If this is set to false the json will be rendered as raw string.
     *
     * @param   bool  $useJsonWrap
     *
     * @return ContentElementControllerContext
     */
    public function setUseJsonWrap(bool $useJsonWrap): ContentElementControllerContext
    {
        $this->useJsonWrap = $useJsonWrap;

        return $this;
    }

    /**
     * Returns the currently configured json wrap html template or null if the default template should be used
     *
     * @return string|null
     */
    public function getJsonWrap(): ?string
    {
        return $this->jsonWrap;
    }

    /**
     * This can be used to overwrite the jsonWrap html template that is applied if $useJsonWrap is set to true.
     * If this is set to null the default json wrap will be used.
     *
     * There are multiple placeholders you can use in your wrap definition:
     * - {{id}} will be replaced with a unique id for this content element
     * - {{definitionAttr}} will be replaced with the elements definition as json object, already html encoded, to be
     * placed in a html5 data attribute. Use the marker {{document}} to mark the spot where the actual json string
     * - {{definitionPretty}} will be replaced with the elements definition as pretty printed json object.
     *
     * @param   string|null  $jsonWrap
     *
     * @return ContentElementControllerContext
     */
    public function setJsonWrap(?string $jsonWrap): ContentElementControllerContext
    {
        $this->jsonWrap = $jsonWrap;

        return $this;
    }

    /**
     * Returns additional data that was passed to the content element handler from the environment.
     * This can be the content object renderer in the frontend, or the backend preview rendering context in the backend.
     *
     * @return array
     */
    public function getEnvironment(): array
    {
        return $this->environment;
    }

    /**
     * Adds one ore more css classes to the list of global content element classes.
     * The classes you set here will be distributed to every content element that is provided in the frontend.
     *
     * @param $classes
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    public function addCssClasses($classes): ContentElementControllerContext
    {
        if (is_string($classes)) {
            $classes = Arrays::makeFromStringList($classes, ' ');
        }
        if (! is_array($classes)) {
            throw new JsonApiException('Invalid css class list given!');
        }
        $this->cssClasses = array_unique(Arrays::attach($this->cssClasses, $classes));

        return $this;
    }

    /**
     * Removes one or multiple classes from the global content element registration
     *
     * @param $classes
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     */
    public function removeCssClasses($classes): ContentElementControllerContext
    {
        if (is_string($classes)) {
            $classes = Arrays::makeFromStringList($classes, ' ');
        }
        if (! is_array($classes)) {
            throw new JsonApiException('Invalid css class list given!');
        }
        $this->cssClasses = array_diff($this->cssClasses, $classes);

        return $this;
    }

    /**
     * Returns the list of all registered css classes.
     *
     * @return array
     */
    public function getCssClasses(): array
    {
        return array_values($this->cssClasses);
    }

    /**
     * Returns the currently set data transformer options
     *
     * @return array
     */
    public function getDataTransformerOptions(): array
    {
        return $this->dataTransformerOptions;
    }

    /**
     * Can be used to set the options for the transformer that is used to convert the data object into an array.
     *
     * @param   array  $dataTransformerOptions
     *                       - allIncludes bool (FALSE): If this is set to true all children of the value
     *                       will be included in the transformed output. By default the auto transformer
     *                       will ignore includes.
     *
     * @return ContentElementControllerContext
     */
    public function setDataTransformerOptions(array $dataTransformerOptions): ContentElementControllerContext
    {
        $this->dataTransformerOptions = $dataTransformerOptions;

        return $this;
    }

    /**
     * Returns true if the loader component should be used for this content element
     *
     * @return bool
     */
    public function useLoaderComponent(): bool
    {
        return $this->useLoaderComponent;
    }

    /**
     * Can be used to determine if the component should show the loader component until it is completely loaded
     *
     * By default all components will show the "loader" static component when you are
     * using the typo-frontend-api. If you set this flag to false, the loader will not
     * be shown. The content element will simply appear when it is done loading
     *
     * @param   bool  $useLoaderComponent
     */
    public function setUseLoaderComponent(bool $useLoaderComponent): void
    {
        $this->useLoaderComponent = $useLoaderComponent;
    }

    /**
     * Factory method to create a new instance of this object
     *
     * @param   string                       $cType        The configured cType for this content element
     * @param   array                        $config       The given typoScript configuration array
     * @param   AbstractContentElementModel  $model        The prepared model of data for ths controller
     * @param   ServerRequestInterface       $request      The request that was used to execute this content element
     * @param   array                        $environment  Additional data that was passed from the environment
     * @param   array                        $cssClasses   The css classes that should be provided to this content element
     *
     * @return \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(
        string $cType,
        array $config,
        AbstractContentElementModel $model,
        ServerRequestInterface $request,
        array $environment,
        array $cssClasses
    ): ContentElementControllerContext {
        /** @var \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext $self */
        return TypoContainer::getInstance()->get(static::class, [
            'args' => [
                $cType,
                $config,
                $model,
                $request,
                $environment,
                $cssClasses,
            ],
        ]);
    }

}
