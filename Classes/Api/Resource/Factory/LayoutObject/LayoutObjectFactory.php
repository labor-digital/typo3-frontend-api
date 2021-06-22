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
 * Last modified: 2021.06.21 at 12:41
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\Api\Resource\Factory\LayoutObject;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\ExtConfig\Traits\SiteConfigAwareTrait;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3fa\Api\Resource\Entity\LayoutObjectEntity;
use LaborDigital\T3fa\Api\Resource\Entity\PageEntity;
use LaborDigital\T3fa\Core\Cache\T3faCacheAwareTrait;
use LaborDigital\T3fa\Core\Resource\Exception\InvalidQueryException;
use LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException;
use LaborDigital\T3fa\Core\Resource\Repository\ResourceRepository;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LayoutObjectFactory
{
    use ContainerAwareTrait;
    use T3faCacheAwareTrait;
    use SiteConfigAwareTrait;
    
    protected $resolvedLayoutCache = [];
    
    /**
     * LayoutObjectFactory constructor.
     *
     * @param   \LaborDigital\T3ba\Tool\TypoContext\TypoContext  $typoContext
     */
    public function __construct(TypoContext $typoContext)
    {
        $this->context = $typoContext;
        $this->registerConfig('t3fa.layoutObject');
    }
    
    /**
     * Generates the instance of a single layout object based on the given identifier
     *
     * @param   string                                    $identifier
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return \LaborDigital\T3fa\Api\Resource\Entity\LayoutObjectEntity
     * @throws \LaborDigital\T3fa\Core\Resource\Exception\ResourceNotFoundException
     */
    public function makeSingle(string $identifier, SiteLanguage $language): LayoutObjectEntity
    {
        $options = $this->getSiteConfig()['options'][$identifier] ?? null;
        
        if (empty($options)) {
            throw new ResourceNotFoundException('There is no layout object with identifier: "' . $identifier . '" available');
        }
        
        $keyArgs = [
            'layoutObject_resource',
            $identifier,
            $language->getTwoLetterIsoCode(),
        ];
        
        if ($options['cachePerPid'] ?? false) {
            $keyArgs[] = $this->context->pid()->getCurrent();
        }
        
        if ($options['cachePerLayout'] ?? false) {
            $keyArgs[] = $this->resolveLayout($language);
        }
        
        return $this->makeInstance(
            LayoutObjectEntity::class,
            $this->getCache()->remember(
                function () use ($identifier, $language) {
                    $generatorClass = $this->getSiteConfig()['objects'][$identifier] ?? null;
                    if (empty($generatorClass) || ! class_exists($generatorClass)) {
                        throw new ResourceNotFoundException('Could not resolve generator for layout object: "' . $identifier . '"');
                    }
                    
                    return $this->getService(DataGenerator::class)->generate($identifier, $generatorClass, $language);
                },
                $keyArgs,
                [
                    'tags' => ['layoutObject'],
                ]
            )
        );
    }
    
    /**
     * Generates the list of registered layout objects
     *
     * @param   SiteLanguage  $language     The language to generate the objects with
     * @param   array|null    $identifiers  Optional list of identifiers to INCLUDE, which allows you to filter
     *                                      which objects are rendered or not.
     *
     * @return LayoutObjectEntity[]
     */
    public function makeCollection(SiteLanguage $language, ?array $identifiers = null): array
    {
        if (empty($identifiers)) {
            $identifiers = array_keys($this->getSiteConfig()['objects'] ?? []);
        }
        
        $list = [];
        foreach ($identifiers as $k => $identifier) {
            if (! is_string($identifier)) {
                throw new InvalidQueryException('The list of identifiers can only contain string values! '
                                                . 'Value: ' . $k . ' isn\'t a string, tho!');
            }
            
            $list[] = $this->makeSingle($identifier, $language);
        }
        
        return $list;
    }
    
    /**
     * We resolve the layout of the current page based on the page resource object
     *
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return string
     */
    protected function resolveLayout(SiteLanguage $language): string
    {
        $pid = $this->context->pid()->getCurrent();
        if (isset($this->resolvedLayoutCache[$pid])) {
            return $this->resolvedLayoutCache[$pid];
        }
        
        $page = $this->getService(ResourceRepository::class)->getResource('page', $pid, [
            'language' => $language,
        ]);
        
        if (! $page || ! $page->getRaw() instanceof PageEntity) {
            return 'default';
        }
        
        return $this->resolvedLayoutCache[$pid]
            = $page->getRaw()->getAttributes()['meta']['layout'] ?? 'default';
    }
}