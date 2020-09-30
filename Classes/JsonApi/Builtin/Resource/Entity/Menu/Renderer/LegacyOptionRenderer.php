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
 * Last modified: 2020.09.29 at 16:04
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer;


use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\PageMenu;
use LaborDigital\Typo3FrontendApi\JsonApi\JsonApiException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LegacyOptionRenderer
 *
 * @package    LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer
 * @deprecated temporary solution to provide legacy support, will be removed in v10
 * @internal   do not use this yourself
 */
class LegacyOptionRenderer extends AbstractMenuRenderer
{
    /**
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu\Renderer\AbstractMenuRenderer
     */
    protected $subRenderer;

    /**
     * LegacyOptionRenderer constructor.
     *
     * @param   string  $type
     */
    public function __construct(string $type)
    {
        switch ($type) {
            case PageMenu::TYPE_MENU_PAGE:
                $this->subRenderer = GeneralUtility::makeInstance(PageMenuRenderer::class);
                break;
            case PageMenu::TYPE_MENU_DIRECTORY:
                $this->subRenderer = GeneralUtility::makeInstance(DirectoryMenuRenderer::class);
                break;
            case PageMenu::TYPE_MENU_ROOT_LINE:
                $this->subRenderer = GeneralUtility::makeInstance(RootLineMenuRenderer::class);
                break;
            default:
                throw new JsonApiException('The menu is not configured correctly! There is no menu type: ' . $type);
        }
    }

    /**
     * Builds the v10 options and returns them
     *
     * @param   array  $options
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayException
     */
    public function getOptions(array $options): array
    {
        return $this->subRenderer->validateMenuOptions($options, $this->subRenderer->getMenuOptionDefinition());
    }

    /**
     * @inheritDoc
     */
    protected function getMenuOptionDefinition(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getMenuTsConfig(array $defaultDefinition): array
    {
        return [];
    }

}
