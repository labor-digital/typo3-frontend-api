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
 * Last modified: 2021.06.22 at 19:17
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Core\ContentElement\Response;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class ResponseFactory implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    /**
     * Internal helper to create a new Json response object inside of an extbase controller
     *
     * @param   \TYPO3\CMS\Extbase\Mvc\Request            $request
     * @param   \TYPO3\CMS\Extbase\Mvc\View\AbstractView  $view
     * @param   array                                     $row
     *
     * @return \LaborDigital\T3fa\Core\ContentElement\Response\JsonResponse
     * @internal
     */
    public function make(Request $request, ViewInterface $view, array $row): JsonResponse
    {
        $cssClasses = $this->resolveCmsCssClasses($row);
        
        return $this->makeInstance(
            JsonResponse::class,
            [
                Inflector::toCamelCase($request->getControllerExtensionName()),
                Inflector::toCamelCase($request->getControllerName()),
                Inflector::toCamelCase($request->getControllerActionName()),
                $view,
                $row,
                $cssClasses,
            ]
        );
    }
    
    /**
     * Resolves the default, framework css classes based on the resolved row
     *
     * @param   array  $row
     *
     * @return array
     */
    protected function resolveCmsCssClasses(array $row): array
    {
        $classFieldMap = [
            'frame frame--' => $row['frame_class'] ?? '',
            'spacerTop spacerTop--' => $row['space_before_class'] ?? '',
            'spacerBottom spacerBottom--' => $row['space_after_class'] ?? '',
        ];
        
        $cssClasses = [];
        
        foreach ($classFieldMap as $prefix => $field) {
            if (empty($field)) {
                continue;
            }
            
            $cssClasses[] = array_map(static function ($v) use ($prefix) {
                return $prefix . $v;
            }, Arrays::makeFromStringList($field));
        }
        
        return array_unique(array_merge(...$cssClasses) ?? []);
    }
    
}