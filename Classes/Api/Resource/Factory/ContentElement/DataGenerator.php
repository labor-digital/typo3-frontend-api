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
 * Last modified: 2021.06.13 at 21:25
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\ContentElement;


use Closure;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3ba\Tool\Tsfe\TsfeService;
use LaborDigital\T3ba\Tool\TypoScript\TypoScriptService;
use LaborDigital\T3fa\Api\Resource\Factory\ContentElement\ContentObject\ThrowingRecordsContentObject;
use LaborDigital\T3fa\Core\Cache\Scope\Scope;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use LaborDigital\T3fa\Core\ContentElement\HtmlSerializer;
use Neunerlei\TinyTimy\DateTimy;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class DataGenerator implements PublicServiceInterface
{
    use ContainerAwareTrait;
    use T3faCacheAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoScript\TypoScriptService
     */
    protected $typoScriptService;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Tsfe\TsfeService
     */
    protected $tsfeService;
    
    public function __construct(
        EnvironmentSimulator $simulator,
        TypoScriptService $typoScriptService,
        TsfeService $tsfeService
    )
    {
        $this->simulator = $simulator;
        $this->typoScriptService = $typoScriptService;
        $this->tsfeService = $tsfeService;
    }
    
    /**
     * Generates the data for a single content element based on its unique id
     *
     * @param   int                                       $uid
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return array
     */
    public function makeFromId(int $uid, SiteLanguage $language): array
    {
        return $this->simulator->runWithEnvironment(
            [
                'language' => $language,
            ],
            function () use ($uid, $language) {
                return $this->process($uid, $language, function () use ($uid) {
                    try {
                        // I have to be a bit creative here,
                        // to find out if a content element exists I use an extension of the records content element
                        // that will throw a not found exception if the result is empty and no data was resolved.
                        $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['RECORDS_THROWING'] = ThrowingRecordsContentObject::class;
                        
                        $cObj = $this->makeInstance(
                            ContentObjectRenderer::class,
                            [
                                $this->tsfeService->getTsfe(),
                                $this->getContainer(),
                            ]
                        );
                        
                        $result = $cObj->cObjGetSingle(
                            'RECORDS_THROWING',
                            [
                                'tables' => 'tt_content',
                                'source' => $uid,
                                'dontCheckPid' => 1,
                            ]
                        );
                        
                        if (ThrowingRecordsContentObject::$lastRenderedPid !== null) {
                            $this->runInCacheScope(static function (Scope $scope): void {
                                $pid = ThrowingRecordsContentObject::$lastRenderedPid;
                                $scope->addCacheTags(['page_' . $pid, 'pages_' . $pid]);
                            });
                        }
                        
                        return $result;
                    } finally {
                        unset($GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['RECORDS_THROWING']);
                    }
                    
                });
            }
        );
        
    }
    
    /**
     * Wrapper around the content generation of a content element that handles
     * html serialized json element data and builds the actual attribute array out of it
     *
     * @param   int|string                                $uidOrPath
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     * @param   \Closure                                  $generator
     *
     * @return array
     */
    protected function process($uidOrPath, SiteLanguage $language, Closure $generator): array
    {
        $attributes = [
            'id' => is_numeric($uidOrPath) ? $uidOrPath : md5($uidOrPath),
            'componentType' => 'html',
            'data' => null,
            'initialState' => null,
            'cssClasses' => null,
            'children' => null,
            'meta' => [
                'language' => $language->getTwoLetterIsoCode(),
                'generated' => (new DateTimy())->formatJs(),
            ],
        ];
        
        $data = $generator();
        
        $unserialized = HtmlSerializer::unserialize($data);
        if ($unserialized) {
            [$data, $meta] = $unserialized;
            
            if (is_array($meta)) {
                // Makes sure that our cache options are announced to the parent scopes
                $this->runInCacheScope(function (Scope $scope) use ($meta) {
                    if (is_array($meta['cache'] ?? null)) {
                        $scope->setCacheOptions($meta['cache'], true);
                    } elseif ($meta['actionFailed'] ?? false) {
                        $scope->setCacheEnabled(false);
                    }
                });
            }
            
            if (is_array($data)) {
                $attributes = array_merge($attributes, $data);
            }
        } else {
            $attributes['data'] = $data;
        }
        
        return array_values([
            'id' => $attributes['id'],
            'attributes' => $attributes,
        ]);
        
    }
}