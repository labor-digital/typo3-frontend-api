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
 * Last modified: 2020.09.25 at 13:03
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu;


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Class PageMenuItemDataProcessor
 *
 * Internal helper to process the main menu entries recursively using the typoScript data processor logic
 *
 * @package LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Menu
 */
class PageMenuItemDataProcessor implements DataProcessorInterface
{
    protected const INVALID_TARGET_MARKER = '{{INVALID_LINK_TARGET}}';

    /**
     * @inheritDoc
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        $options = $processorConfiguration['options'];
        /** @var \LaborDigital\Typo3FrontendApi\Shared\FrontendApiContext $context */
        $context = $processorConfiguration['context'];

        // Extract the data from the element
        $data = $processedData['data'];
        unset($processedData['data']);

        // Provide a cache tag
        $context->CacheService()->announceTag('pages_' . $data['uid']);

        // Inherit the required additional fields
        $processedData['fields'] = array_intersect_key($data, array_fill_keys($options['additionalFields'], null));
        $transformer             = $context->TransformerFactory()->getTransformer();
        foreach ($options['fileFields'] as $fileField) {
            $tempName = 'fileField.' . $fileField;
            if (is_array($processedData[$tempName])) {
                $processedData['fields'][$fileField] = array_map([$transformer, 'transform'], $processedData[$tempName]);
                unset($processedData[$tempName]);
            }
        }

        // Handle links that reference a hidden/deleted page
        if (empty($processedData['link'])) {
            return static::INVALID_TARGET_MARKER;
        }

        // Generate additional information
        $processedData['children'] = static::removeInvalidMarkers($processedData['children']) ?? null;
        $processedData['id']       = $data['uid'];
        $processedData['type']     = $this->findLinkType($data, $processedData['link']);
        $processedData['inMenu']   = $data['nav_hide'] !== 1;

        // Remove elements not relevant for SPA menus
        unset($processedData['active'], $processedData['current'], $processedData['spacer']);

        // Generate backward compatible entries
        // @todo remove this in v10
        $processedData['href'] = $processedData['link'];

        // Run the post processor if required
        if (! empty($processorConfiguration['postProcessor'])) {
            /** @var \LaborDigital\Typo3FrontendApi\Site\Configuration\PageMenuItemPostProcessorInterface $processor */
            $processor     = $processorConfiguration['postProcessor'];
            $processedData = $processor->processItem(
                $processorConfiguration['key'],
                $processedData,
                $data,
                $processorConfiguration['options'],
                $processorConfiguration['type']
            );
        }

        return $processedData;
    }

    /**
     * Makes the numeric dokType value more readable for the api
     *
     * @param   array   $data
     * @param   string  $link
     *
     * @return string
     */
    protected function findLinkType(array $data, string $link): string
    {
        $dokType = $data['doktype'];

        if ($dokType === 199) {
            return PageMenu::TYPE_LINK_SPACER;
        }

        if ($dokType === 3) {
            if (! GeneralUtility::isOnCurrentHost($link)) {
                return PageMenu::TYPE_LINK_EXTERNAL;
            }

            return PageMenu::TYPE_LINK_INTERNAL;
        }

        return PageMenu::TYPE_LINK_PAGE;
    }

    public static function removeInvalidMarkers(?array $list): ?array
    {
        return is_array($list) ? array_values(array_filter($list, function ($v) {
            return $v !== static::INVALID_TARGET_MARKER;
        })) : $list;
    }

}
