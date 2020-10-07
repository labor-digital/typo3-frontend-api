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
 * Last modified: 2019.09.20 at 14:12
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\ArrayBasedCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\PageMenu;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;

class CommonElement implements SelfTransformingInterface
{
    use FrontendApiContextAwareTrait;

    public const TYPE_CONTENT_ELEMENT = 'contentElement';
    public const TYPE_TYPO_SCRIPT     = 'ts';
    public const TYPE_MENU            = 'menu';
    public const TYPE_CUSTOM          = 'custom';

    /**
     * The object key for this element
     *
     * @var string
     */
    protected $key;

    /**
     * The layout this common element is requested for
     *
     * @var string
     */
    protected $layout;

    /**
     * CommonElement constructor.
     *
     * @param   string  $layout  The layout this common element is requested for
     * @param   string  $key     The object key for this element
     */
    public function __construct(string $layout, string $key)
    {
        $this->layout = $layout;
        $this->key    = $key;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        // Check if we got this element
        $context    = $this->FrontendApiContext();
        $siteConfig = $context->getCurrentSiteConfig();
        $layout     = $this->layout;
        if (empty($elementList[$layout])) {
            $layout = 'default';
        }
        if (! isset($siteConfig->commonElements[$layout], $siteConfig->commonElements[$layout][$this->key])) {
            throw new JsonApiException('There is no common element with the given key: ' . $this->key);
        }

        // Retrieve the data
        $key    = $this->key;
        $config = $siteConfig->commonElements[$layout][$key];
        $type   = $config['type'];
        $data   = $context->CacheService()->remember(static function () use ($config, $key, $context, $type) {
            if ($type === static::TYPE_CONTENT_ELEMENT || $type === static::TYPE_TYPO_SCRIPT) {
                return $context->getInstanceWithoutDi(ContentElement::class, [
                    $type === static::TYPE_CONTENT_ELEMENT ? ContentElement::TYPE_TT_CONTENT : ContentElement::TYPE_TYPO_SCRIPT,
                    $config['value'],
                    $context->getLanguageCode(),
                ])->asArray();
            }

            if ($type === static::TYPE_MENU) {
                return $context->getInstanceWithoutDi(PageMenu::class, [
                    $key,
                    $config['value']['type'],
                    $config['value']['options'],
                ])->asArray();
            }

            if ($type === static::TYPE_CUSTOM) {
                $transformer = $context->TransformerFactory()->getTransformer();

                return $transformer->transform(
                    $context->getInstanceOf($config['value']['class'])->asArray($key, $config['value']['data'])
                );
            }

            throw new JsonApiException('Could not render a common element with type: ' . $type);

        }, [
            'tags'         => ['commonElement_' . $layout . '_' . $key],
            'keyGenerator' => $context->getInstanceWithoutDi(ArrayBasedCacheKeyGenerator::class,
                [[__CLASS__, $siteConfig->siteIdentifier, $key, $layout, $type, $config]]),
        ]);

        // Done
        return [
            'id'          => $this->key,
            'layout'      => $this->layout,
            'elementType' => $config['type'],
            'element'     => $data,
        ];
    }

    /**
     * Creates a new instance of myself
     *
     * @param   string  $layout
     * @param   string  $key
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\CommonElement
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance(string $layout, string $key): CommonElement
    {
        return TypoContainer::getInstance()->get(static::class, ['args' => [$layout, $key]]);
    }
}
