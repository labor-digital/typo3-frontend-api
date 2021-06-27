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
 * Last modified: 2021.06.04 at 12:41
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
 * Last modified: 2019.09.18 at 18:27
 */

namespace LaborDigital\T3fa\Domain\DataModel\Page;


use LaborDigital\T3ba\ExtBase\Domain\Model\BetterEntity;

class DefaultPageDataModel extends BetterEntity
{
    /**
     * @var string|null
     */
    protected $title;
    
    /**
     * @var string|null
     */
    protected $navTitle;
    
    /**
     * @var string|null
     */
    protected $slug;
    
    /**
     * @var string|null
     */
    protected $seoTitle;
    
    /**
     * @var bool|null
     */
    protected $noFollow;
    
    /**
     * @var bool|null
     */
    protected $noIndex;
    
    /**
     * @return string|null
     */
    public function getTitle(): string
    {
        return (string)$this->title;
    }
    
    /**
     * @return string
     */
    public function getNavTitle(): string
    {
        return $this->navTitle;
    }
    
    /**
     * @param   string  $navTitle
     *
     * @return DefaultPageDataModel
     */
    public function setNavTitle(string $navTitle): DefaultPageDataModel
    {
        $this->navTitle = $navTitle;
        
        return $this;
    }
    
    public function getSlug(): string
    {
        return (string)$this->slug;
    }
    
    public function setSlug(string $slug): DefaultPageDataModel
    {
        $this->slug = $slug;
        
        return $this;
    }
    
    public function setTitle(string $title): DefaultPageDataModel
    {
        $this->title = $title;
        
        return $this;
    }
    
    public function getSeoTitle(): string
    {
        return (string)$this->seoTitle;
    }
    
    public function setSeoTitle(string $seoTitle): DefaultPageDataModel
    {
        $this->seoTitle = $seoTitle;
        
        return $this;
    }
    
    public function isNoFollow(): bool
    {
        return (bool)$this->noFollow;
    }
    
    public function setNoFollow(bool $noFollow): DefaultPageDataModel
    {
        $this->noFollow = $noFollow;
        
        return $this;
    }
    
    public function isNoIndex(): bool
    {
        return (bool)$this->noIndex;
    }
    
    public function setNoIndex(bool $noIndex): DefaultPageDataModel
    {
        $this->noIndex = $noIndex;
        
        return $this;
    }
}
