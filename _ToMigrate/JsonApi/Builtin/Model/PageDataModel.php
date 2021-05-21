<?php
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

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Model;


use LaborDigital\Typo3BetterApi\Domain\Model\BetterEntity;

class PageDataModel extends BetterEntity
{

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $navTitle;

    /**
     * @var string
     */
    protected $slug;

    /**
     * @var string
     */
    protected $seoTitle;

    /**
     * @var bool
     */
    protected $noFollow;

    /**
     * @var bool
     */
    protected $noIndex;

    /**
     * @return string
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
     * @return PageDataModel
     */
    public function setNavTitle(string $navTitle): PageDataModel
    {
        $this->navTitle = $navTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param   string  $slug
     *
     * @return PageDataModel
     */
    public function setSlug(string $slug): PageDataModel
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @param   string  $title
     *
     * @return PageDataModel
     */
    public function setTitle(string $title): PageDataModel
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getSeoTitle(): string
    {
        return (string)$this->seoTitle;
    }

    /**
     * @param   string  $seoTitle
     *
     * @return PageDataModel
     */
    public function setSeoTitle(string $seoTitle): PageDataModel
    {
        $this->seoTitle = $seoTitle;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNoFollow(): bool
    {
        return (bool)$this->noFollow;
    }

    /**
     * @param   bool  $noFollow
     *
     * @return PageDataModel
     */
    public function setNoFollow(bool $noFollow): PageDataModel
    {
        $this->noFollow = $noFollow;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNoIndex(): bool
    {
        return (bool)$this->noIndex;
    }

    /**
     * @param   bool  $noIndex
     *
     * @return PageDataModel
     */
    public function setNoIndex(bool $noIndex): PageDataModel
    {
        $this->noIndex = $noIndex;

        return $this;
    }
}
