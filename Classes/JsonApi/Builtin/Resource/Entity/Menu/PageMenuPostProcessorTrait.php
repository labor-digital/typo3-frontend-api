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
 * Last modified: 2020.09.29 at 15:32
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu;


use InvalidArgumentException;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer\AbstractMenuRenderer;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;

trait PageMenuPostProcessorTrait
{
    use FrontendApiContextAwareTrait;

    /**
     * Renders a menu based on the given configuration.
     * This can be useful to render submenus in post processors that could not be realized in the built-in solution
     *
     * @param   string  $key            A unique key for this menu -> Will be used for event handlers as a unique key
     * @param   string  $rendererClass  One of the available MenuRenderers that extend {@link AbstractMenuRenderer}
     * @param   array   $options        Additional options for the renderer {@see SiteConfigurator::registerPageMenu()}
     *                                  and the other menu functions for more details
     *
     *                                  - levelOffset int: Allows you to offset the menu level automatically,
     *                                  this is useful if you render submenus
     *
     * @return array
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException
     * @throws \Neunerlei\Arrays\ArrayException
     */
    protected function renderMenu(string $key, string $rendererClass, array $options): array
    {
        if (! class_exists($rendererClass) || ! in_array(AbstractMenuRenderer::class, class_parents($rendererClass), true)) {
            throw new InvalidArgumentException(
                'The given renderer class: "' . $rendererClass . '" is invalid! The class must exist and extend: "' .
                AbstractMenuRenderer::class . '"!');
        }

        /** @var AbstractMenuRenderer $renderer */
        $renderer = $this->FrontendApiContext()->getSingletonOf($rendererClass);

        // Render the menu including the level offset
        $levelOffsetBackup = ExtendedMenuProcessor::$levelOffset;
        try {
            ExtendedMenuProcessor::$levelOffset = (int)$options['levelOffset'] ?? 0;
            unset($options['levelOffset']);

            return $renderer->render($key, $options);
        } finally {
            ExtendedMenuProcessor::$levelOffset = $levelOffsetBackup;
        }
    }
}
