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
 * Last modified: 2021.06.21 at 16:27
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\LayoutObject\Renderer\Menu;


use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
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
    use T3faCacheAwareTrait;
    
    protected const INVALID_TARGET_MARKER = '{{INVALID_LINK_TARGET}}';
    
    /**
     * The cache tags generated by the last process() execution
     *
     * @var array
     */
    public static $cacheTags = [];
    
    /**
     * @inheritDoc
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        $options = $processorConfiguration['options'];
        $autoTransformer = $processorConfiguration['autoTransformer'];
        
        // Extract the data from the element
        $data = $processedData['data'];
        unset($processedData['data']);
        
        // Provide a cache tag
        self::$cacheTags[] = 'pages_' . $data['uid'];
        
        // Inherit the required additional fields
        $processedData['fields'] = array_intersect_key($data, array_fill_keys($options['additionalFields'], null));
        foreach ($options['fileFields'] as $fileField) {
            $tempName = 'fileField.' . $fileField;
            if (is_array($processedData[$tempName])) {
                $processedData['fields'][$fileField]
                    = array_map([$autoTransformer, 'transform'], $processedData[$tempName]);
                unset($processedData[$tempName]);
            }
        }
        
        // Handle links that reference a hidden/deleted page
        if (! is_string($processedData['link'])) {
            return static::INVALID_TARGET_MARKER;
        }
        
        // Generate additional information
        $processedData['children'] = static::removeInvalidMarkers($processedData['children']);
        $processedData['id'] = $data['uid'];
        $processedData['type'] = $this->findLinkType($data, $processedData['link']);
        $processedData['inMenu'] = $data['nav_hide'] !== 1;
        $processedData['hidden'] = $data['hidden'] === 1;
        
        // Remove elements not relevant for SPA menus
        unset($processedData['active'], $processedData['current'], $processedData['spacer']);
        
        // Run the post processor if required
        if (! empty($processorConfiguration['postProcessor'])) {
            /** @var PageMenuItemPostProcessorInterface $processor */
            $processor = $processorConfiguration['postProcessor'];
            $processedData = $processor->processItem(
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
            return AbstractMenuRenderer::TYPE_LINK_SPACER;
        }
        
        if ($dokType === 3) {
            if (! GeneralUtility::isOnCurrentHost($link)) {
                return AbstractMenuRenderer::TYPE_LINK_EXTERNAL;
            }
            
            return AbstractMenuRenderer::TYPE_LINK_INTERNAL;
        }
        
        return AbstractMenuRenderer::TYPE_LINK_PAGE;
    }
    
    /**
     * Helper to remove all invalid link targets from the given list of entries.
     *
     * @param   array|null  $list
     *
     * @return array|null
     */
    public static function removeInvalidMarkers(?array $list): ?array
    {
        return is_array($list) ? array_values(array_filter($list, function ($v) {
            return $v !== static::INVALID_TARGET_MARKER;
        })) : $list;
    }
    
}
