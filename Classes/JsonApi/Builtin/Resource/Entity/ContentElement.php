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
 * Last modified: 2019.09.26 at 17:25
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use Closure;
use InvalidArgumentException;
use LaborDigital\Typo3BetterApi\Container\LazyServiceDependencyTrait;
use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\Event\TypoEventBus;
use LaborDigital\Typo3BetterApi\Tsfe\TsfeService;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3BetterApi\TypoScript\TypoScriptService;
use LaborDigital\Typo3FrontendApi\ContentElement\ContentElementHandler;
use LaborDigital\Typo3FrontendApi\ContentElement\SpaContentPreparedException;
use LaborDigital\Typo3FrontendApi\Event\ContentElementSpaEvent;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use Neunerlei\TinyTimy\DateTimy;

class ContentElement implements SelfTransformingInterface
{
    use LazyServiceDependencyTrait;
    
    public const TYPE_TT_CONTENT  = 0;
    public const TYPE_TYPO_SCRIPT = 1;
    public const TYPE_MANUAL      = 2;
    
    /**
     * The uid that defines this content element
     *
     * @var int|null|string
     */
    public $uid;
    
    /**
     * The frontend component identifier that should render this element
     *
     * @var string
     */
    public $componentType;
    
    /**
     * Additional data that was provided by the backend and should be used by the frontend component
     *
     * @var array|null|mixed
     */
    public $data;
    
    /**
     * The generated initial state object of this element
     *
     * @var mixed
     */
    public $initialState;
    
    /**
     * The two char iso language code for this element
     *
     * @var string
     */
    public $languageCode;
    
    /**
     * A list of additional css classes that should be provided for this content element
     *
     * @var array
     */
    public $cssClasses = [];
    
    /**
     * Optional additional attributes that are not implemented by this class
     *
     * @var array
     */
    public $additionalAttributes = [];
    
    /**
     * Holds the column list of the children of this content element or is null
     *
     * @var ContentElementColumnList|null
     */
    public $children;
    
    /**
     * By default all components will show the "loader" static component when you are
     * using the typo-frontend-api. If you set this flag to false, the loader will not
     * be shown. The content element will simply appear when it is done loading
     *
     * @var bool
     */
    public $useLoaderComponent = true;
    
    /**
     * True if the spa event listener is already bound
     *
     * @var bool
     */
    protected static $listenerBound = false;
    
    /**
     * The listener that is executed when the spa event is dispatched
     *
     * @var callable|null
     */
    protected static $listener;
    
    /**
     * The type of content element to render -> Use one of the TYPE_ constants
     *
     * @var int
     */
    protected $type;
    
    /**
     * The source to gather the content with
     *
     * @var mixed
     */
    protected $source;
    
    /**
     * True if the element is already populated
     *
     * @var bool
     */
    protected $isPopulated = false;
    
    /**
     * ContentElement constructor.
     *
     * @param   int              $type    The type of content element to render -> Use one of the TYPE_ constants
     * @param   null|int|string  $source  The source to gather the content with:
     *                                    If $type = TYPE_TT_CONTENT OR $type = TYPE_MANUAL the uid of the tt_content
     *                                    record to render If $type = TYPE_TYPO_SCRIPT the TypoScript Object path to
     *                                    render
     * @param   TypoContext      $context
     */
    public function __construct(int $type, $source, TypoContext $context)
    {
        $this->uid          = is_numeric($source) ? (int)$source
            : md5(microtime(true) . random_int(0, 10) . random_bytes(10));
        $this->languageCode = $context->Language()->getCurrentFrontendLanguage()->getTwoLetterIsoCode();
        $this->type         = $type;
        $this->source       = $source;
    }
    
    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        // Use a generator based on the given type
        if (! $this->isPopulated) {
            $this->isPopulated = true;
            switch ($this->type) {
                case static::TYPE_TT_CONTENT:
                    if (! is_numeric($this->source)) {
                        throw new InvalidArgumentException("The given \$source argument has to be a numeric uid of a tt_content record!");
                    }
                    
                    // Render the content element based on the uid
                    $this->populateMyself(function () {
                        return $this->getService(TypoScriptService::class)->renderContentObject("RECORDS", [
                            "tables"       => "tt_content",
                            "source"       => $this->uid,
                            "dontCheckPid" => 1,
                        ]);
                    });
                    break;
                case static::TYPE_TYPO_SCRIPT:
                    if (! is_string($this->source) || empty($this->source)) {
                        throw new InvalidArgumentException("The given \$source argument has to be the TypoScript selector of an object to render!");
                    }
                    
                    // Render the content element based on the given object path
                    $this->populateMyself(function () {
                        return $this->getService(TypoScriptService::class)->renderContentObjectWith($this->source);
                    });
                    break;
                case static::TYPE_MANUAL:
                    // Don't do anything...
                    break;
                default:
                    throw new InvalidArgumentException("Invalid \$type given. Refer to the TYPE_ constants of this object!");
            }
        }
        
        return array_merge([
            "type"               => "contentElement",
            "id"                 => $this->uid,
            "componentType"      => $this->componentType,
            "initialState"       => $this->initialState instanceof SelfTransformingInterface ?
                $this->initialState->asArray() : $this->initialState,
            "data"               => $this->data,
            "languageCode"       => $this->languageCode,
            "children"           => $this->asArrayChildrenRecursive($this->children),
            "useLoaderComponent" => $this->useLoaderComponent,
            "generated"          => (new DateTimy())->formatJs(),
            "cssClasses"         => $this->cssClasses,
        ], $this->additionalAttributes);
    }
    
    /**
     * Internal helper which is used to recursively traverse the child-elements of this content element
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElementColumnList|null  $children
     *
     * @return array
     */
    protected function asArrayChildrenRecursive(?ContentElementColumnList $children): array
    {
        if ($children === null) {
            return [];
        }
        
        return $children->asArray();
    }
    
    /**
     * Internal helper which is used to populate the instance
     * based on either the content element's uid -> which will render the content element
     * using the content element handler, or by a given typoScript object path,
     * which is then used to render a static element
     *
     * @param   \Closure  $generator
     */
    protected function populateMyself(Closure $generator): void
    {
        // Store backups
        $listenerBackup = static::$listener;
        $spaModeBackup  = ContentElementHandler::$spaMode;
        
        // Prepare the spa event handler
        static::$listener = function (ContentElementSpaEvent $event) {
            foreach (get_object_vars($event->getElement()) as $k => $v) {
                $this->$k = $v;
            }
            $event->setKillHandler();
        };
        
        // Register the handler
        ContentElementHandler::$spaMode = true;
        if (! static::$listenerBound) {
            $this->getService(TypoEventBus::class)
                 ->addListener(ContentElementSpaEvent::class, static function (ContentElementSpaEvent $event) {
                     if (empty(static::$listener)) {
                         return;
                     }
                     call_user_func(static::$listener, $event);
                 });
        }
        static::$listenerBound = true;
        
        // Render the element
        $tsfe                      = $this->getService(TsfeService::class)->getTsfe();
        $cObjectDepthCounterBackup = $tsfe->cObjectDepthCounter;
        
        // Render the content element using the generator function
        try {
            $this->data          = $generator();
            $this->componentType = "html";
        } catch (SpaContentPreparedException $e) {
            // This is expected...
        }
        
        // Restore the depth counter
        $tsfe->cObjectDepthCounter = $cObjectDepthCounterBackup;
        
        // Unbind the handler
        static::$listener               = $listenerBackup;
        ContentElementHandler::$spaMode = $spaModeBackup;
    }
    
    /**
     * A factory method to create a new, empty instance of myself
     *
     * @param   int|null  $uid
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(?int $uid = null): self
    {
        return TypoContainer::getInstance()
                            ->get(__CLASS__, ["args" => [static::TYPE_TT_CONTENT, $uid]]);
    }
    
    /**
     * Factory method to create a new instance of myself, which is automatically
     * populated with the content element data for the row of tt_content which holds the given $uid
     *
     * @param   int  $uid
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstanceElementWithAutomaticPopulation(int $uid): self
    {
        return TypoContainer::getInstance()
                            ->get(__CLASS__, ["args" => [static::TYPE_TT_CONTENT, $uid]]);
    }
    
    /**
     * Factory method to create a new instance of myself, which is automatically
     * populated with the result of the typoScript rendering for the element with a certain typoScript path.
     * This does not necessary mean that the typoScript object has to render a record, the method will
     * convert anything that is NOT a content element into a "html" content element with the static html
     * as "data", so the frontend does not have to concern itself with the additional overhead.
     *
     * @param   string  $typoScriptObjectPath
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstanceWithTypoScriptPopulation(string $typoScriptObjectPath): self
    {
        return TypoContainer::getInstance()->get(__CLASS__, [
            "args" => [static::TYPE_TYPO_SCRIPT, $typoScriptObjectPath],
        ]);
    }
}
