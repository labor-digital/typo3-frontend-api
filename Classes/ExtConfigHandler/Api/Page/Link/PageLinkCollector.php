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
 * Last modified: 2021.06.02 at 20:35
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Api\Page\Link;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3ba\Tool\Link\LinkService;

class PageLinkCollector implements NoDiInterface
{
    
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    /**
     * The list of registered links by their key
     *
     * @var string[]
     */
    protected $links = [];
    
    public function __construct(LinkService $linkService)
    {
        $this->linkService = $linkService;
    }
    
    /**
     * Adds a new, static link based on a given link object
     * The kind of link generated is up to you, you can either provide a "linkSet" key
     * and omit $link, or provide a unique link and define your link using the TypoLink interface
     *
     * @param   string     $key   The key of a link set or a unique id for the generated link
     * @param   Link|null  $link  Can be omitted if a link set should be used or a link if one was
     *                            defined manually
     *
     * @return $this
     */
    public function registerLink(string $key, ?Link $link = null): self
    {
        $link = $link ?? $this->linkService->getLink($key);
        $this->links[$key] = rtrim($link->build(), '/');
        
        return $this;
    }
    
    /**
     * Registers a new link using a TypoLink configuration.
     *
     * @param   string        $key         The unique id for the generated link
     * @param   array|string  $linkConfig  Can by either a textual representation, like t3://page?uid=26
     *                                     or a full blown typoScript config array which will be rendered.
     *
     * @return $this
     */
    public function registerTypoLink(string $key, $linkConfig): self
    {
        $this->links[$key] = rtrim($this->linkService->getTypoLink($linkConfig), '/');
        
        return $this;
    }
    
    /**
     * Adds a static link to the list. Note: there is no validation of your link is a valid url or not.
     *
     * @param   string  $key   The unique id for the link to add
     * @param   string  $link  The url to add to the link list
     *
     * @return $this
     */
    public function registerStaticLink(string $key, string $link): self
    {
        $this->links[$key] = rtrim($link, '/');
        
        return $this;
    }
    
    /**
     * Removes a previously registered link with the given key
     *
     * @param   string  $key  The key of the link to remove
     *
     * @return $this
     */
    public function removeLink(string $key): self
    {
        unset($this->links[$key]);
        
        return $this;
    }
    
    /**
     * Checks if a link with the given key exists or not
     *
     * @param   string  $key  The key of the link to check for
     *
     * @return bool
     */
    public function hasLink(string $key): bool
    {
        return isset($this->links[$key]);
    }
    
    /**
     * Returns the list of all registered links in the collector
     *
     * @return string[]
     */
    public function getAll(): array
    {
        return $this->links;
    }
}