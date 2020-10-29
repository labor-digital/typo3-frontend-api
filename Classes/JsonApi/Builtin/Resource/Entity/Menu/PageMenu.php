<?php
/*
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.09.25 at 12:17
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
 * Last modified: 2019.09.23 at 06:16
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu;


use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer\AbstractMenuRenderer;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer\DirectoryMenuRenderer;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer\LegacyOptionRenderer;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer\PageMenuRenderer;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer\RootLineMenuRenderer;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;

class PageMenu implements SelfTransformingInterface
{
    use FrontendApiContextAwareTrait;
    use PageMenuDeprecationTrait;

    /**
     * @deprecated will be removed in v10
     */
    public const TYPE_MENU_ROOT_LINE = 'rootLineMenu';
    /**
     * @deprecated will be removed in v10
     */
    public const TYPE_MENU_PAGE = 'pageMenu';
    /**
     * @deprecated will be removed in v10
     */
    public const TYPE_MENU_DIRECTORY = 'dirMenu';

    /**
     * Is a link that is probably handled by the router of the frontend framework
     */
    public const TYPE_LINK_PAGE = 'linkPage';

    /**
     * Is a link that is on the current site, but should be handled as normal html link.
     */
    public const TYPE_LINK_INTERNAL = 'linkInternal';

    /**
     * This link is not on the current host and should probably open in a new tab.
     */
    public const TYPE_LINK_EXTERNAL = 'linkExternal';

    /**
     * This is a pseudo element and not a real link.
     * It is only active if the "showSpacer" option is enabled.
     */
    public const TYPE_LINK_SPACER = 'spacer';


    /**
     * The key that was given for this menu/common element
     *
     * @var string
     */
    protected $key;

    /**
     * The value of one of the TYPE_MENU_ constants, defining the type of menu to render
     *
     * @var string
     */
    protected $type;

    /**
     * The options that are defining how the menu should look like
     *
     * @var array
     */
    protected $options;

    /**
     * PageMenu constructor.
     *
     * @param   string  $key      The key that was given for this menu/common element
     * @param   string  $type     The value of one of the TYPE_MENU_ constants
     * @param   array   $options  The options that are defining how the menu should look like
     */
    public function __construct(string $key, string $type, array $options)
    {
        $this->key     = $key;
        $this->type    = $type;
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        // Translate type to renderer class
        if (! class_exists($this->type)) {
            // Handle legacy definitions
            // @todo remove this in v10
            if ($this->options['useV10Renderer'] !== true && ! in_array('useV10Renderer', $this->options, true)) {
                // Build the legacy options using the v10 options
                $this->options = $this->FrontendApiContext()->getInstanceWithoutDi(
                    LegacyOptionRenderer::class, [$this->type]
                )->getOptions($this->options);

                switch ($this->type) {
                    case static::TYPE_MENU_PAGE:
                        return $this->renderLegacyPageMenu();
                    case static::TYPE_MENU_DIRECTORY:
                        return $this->renderLegacyDirectoryMenu();
                    case static::TYPE_MENU_ROOT_LINE:
                        return $this->renderLegacyRootLineMenu();
                    default:
                        throw new JsonApiException('The menu is not configured correctly! There is no menu type: ' . $this->type);
                }
            }

            $class = ([
                static::TYPE_MENU_PAGE      => PageMenuRenderer::class,
                static::TYPE_MENU_DIRECTORY => DirectoryMenuRenderer::class,
                static::TYPE_MENU_ROOT_LINE => RootLineMenuRenderer::class,
            ])[$this->type];
        } else {
            $class = $this->type;
        }

        // Render the menu
        /** @var AbstractMenuRenderer $renderer */
        $renderer = $this->FrontendApiContext()->getSingletonOf($class);

        return $renderer->render($this->key, $this->options);
    }
}
