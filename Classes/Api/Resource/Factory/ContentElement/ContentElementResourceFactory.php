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
 * Last modified: 2021.06.21 at 20:26
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\ContentElement;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Database\DbService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3fa\Api\Resource\Entity\ContentElementEntity;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class ContentElementResourceFactory
{
    use ContainerAwareTrait;
    use T3faCacheAwareTrait;
    use TypoContextAwareTrait;
    
    /**
     * The list of database rows by their uid we retrieved
     *
     * @var array
     */
    protected $resolvedRows = [];
    
    /**
     * A runtime first-level cache of resolved uri hashes based on the uid of the element
     *
     * @var array
     */
    protected $resolvedQueryParameters = [];
    
    /**
     * Creates a new content element entity from a unique id and a language
     *
     * @param   int           $uid       The unique id of the element to render
     * @param   SiteLanguage  $language  The language code to render the element with
     *
     * @return \LaborDigital\T3fa\Api\Resource\Entity\ContentElementEntity
     */
    public function makeFromId(int $uid, SiteLanguage $language): ContentElementEntity
    {
        // If no query parameters are present -> we don't need to know the ctype
        if (empty($this->getQueryParams())) {
            return $this->makeFromRow(['uid' => $uid, 'sys_language_uid' => $language->getLanguageId()]);
        }
        
        // Retrieve the row to detect query based cache entries
        return $this->makeFromRow($this->resolveRowById($uid, $language));
    }
    
    /**
     * Creates a new content element entity for an existing tt_content row
     *
     * @param   array          $row
     * @param   callable|null  $childGenerator  Optional callback to generate the entries of the "children" sub-array.
     *                                          The children are generated in the same cache scope than the element
     *                                          Meaning if a child is updated the parent element will be recreated as well.
     *                                          The generator must return a column array containing the already transformed elements.
     *                                          It receives the parent element attributes, the id and the language
     *
     * @return \LaborDigital\T3fa\Api\Resource\Entity\ContentElementEntity
     */
    public function makeFromRow(array $row, ?callable $childGenerator = null): ContentElementEntity
    {
        $languageId = $row['sys_language_uid'] ?? $this->getTypoContext()->language()->getId();
        
        if (! is_numeric($languageId)) {
            throw new InvalidArgumentException('The given row is invalid! The sys_language_uid value must be an integer');
        }
        
        $language = $this->getTypoContext()->language()->getLanguageById((int)$languageId);
        
        if (! is_numeric($row['uid'] ?? null)) {
            throw new InvalidArgumentException('The given row is invalid! The uid value must be an integer');
        }
        
        $uid = $row['uid'];
        
        $data = $this->getCache()->remember(
            function () use ($uid, $language) {
                return $this->getService(DataGenerator::class)->makeFromId($uid, $language);
            },
            [
                'ce_resource',
                $uid,
                $language->getTwoLetterIsoCode(),
                '@query' => $this->findQueryParameterNs($row),
            ],
            [
                'tags' => ['contentElement', 'tt_content_' . $uid],
            ]
        );
        
        // The child generation is not cacheable, because we don't know which GET parameters
        // should be considered a "part" of the cacheable content.
        // Therefore, we cache each element separately
        if (is_callable($childGenerator) && ! is_array($data[1]['children'] ?? null)) {
            $children = $childGenerator($data, $uid, $language);
            if (is_array($children)) {
                ksort($children);
                $data[1]['children'] = $children;
            }
        }
        
        return $this->makeInstance(ContentElementEntity::class, $data);
    }
    
    /**
     * Generates a new content element entity based on a typo script definition at the provided path.
     *
     * @param   string                                    $typoScriptObjectPath
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return \LaborDigital\T3fa\Api\Resource\Entity\ContentElementEntity
     */
    public function makeFromTypoScriptPath(string $typoScriptObjectPath, SiteLanguage $language): ContentElementEntity
    {
        return $this->makeInstance(
            ContentElementEntity::class,
            $this->getService(DataGenerator::class)->makeFromTsPath($typoScriptObjectPath, $language)
        );
    }
    
    /**
     * Internal helper to find the tt_content row only by uid and language
     *
     * @param   int                                       $uid
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return array
     */
    protected function resolveRowById(int $uid, SiteLanguage $language): array
    {
        $key = $uid . '.' . $language->getTwoLetterIsoCode();
        if (isset($this->resolvedRows[$key])) {
            return $this->resolvedRows[$key];
        }
        
        $row = $this->getService(DbService::class)
                    ->getQuery('tt_content')
                    ->withLanguage($language)
                    ->withWhere(['uid' => $uid])->getFirst();
        
        return $this->resolvedRows[$key] = $row;
    }
    
    /**
     * Tries to find the plugin/element signature that defines the extbase query namespace for
     * the element in the given row
     *
     * @param   array  $row
     *
     * @return string
     */
    protected function findQueryParameterNs(array $row): string
    {
        $cType = $row['CType'] ?? null;
        if (! $cType) {
            return '';
        }
        
        $cacheNs = 'tx_' . $cType;
        if ($cType === 'list' && ! empty($row['list_type'])) {
            $cacheNs = 'tx_' . $row['list_type'];
        }
        
        return $cacheNs;
    }
    
    /**
     * Returns the list of given query parameters for this request
     *
     * @return array
     */
    protected function getQueryParams(): array
    {
        $request = $this->getTypoContext()->request()->getRootRequest();
        if (! $request) {
            return [];
        }
        
        return $request->getQueryParams();
    }
}