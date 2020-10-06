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
 * Last modified: 2020.09.30 at 10:24
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\Link\TypoLink;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\ArrayBasedCacheKeyGenerator;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;

class PageLinks implements SelfTransformingInterface
{
    use FrontendApiContextAwareTrait;

    /**
     * The pid that was used to request the static links
     *
     * @var int
     */
    protected $pid;

    /**
     * The list of registered links by their key
     *
     * @var TypoLink[]
     */
    protected $links = [];

    /**
     * PagePid constructor.
     *
     * @param   int  $pid
     */
    public function __construct(int $pid)
    {
        $this->pid = $pid;
    }

    /**
     * Returns the pid that was used to request the pid context
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * Adds a new, static link for your frontend to use.
     * The kind of link generated is up to you, you can either provide a "linkSet" key
     * and omit $link, or provide a unique link and define your link using the TypoLink interface
     *
     * @param   string         $key   The key of a link set or a unique id for the generated link
     * @param   TypoLink|null  $link  Can be omitted if a link set should be used or a link if one was
     *                                defined manually
     *
     * @return $this
     */
    public function addLink(string $key, ?TypoLink $link = null): self
    {
        $this->links[$key] = $link ?? $this->FrontendApiContext()->Links()->getLink($key);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $context    = $this->FrontendApiContext();
        $siteConfig = $context->ConfigRepository()->site()->getCurrentSiteConfig();
        if (empty($siteConfig->linkProviders)) {
            return [];
        }

        return $context->CacheService()->remember(function () use ($context, $siteConfig) {
            $linkService = $context->Links();

            // Collect the links
            $this->links = [];
            foreach ($siteConfig->linkProviders as $linkProvider) {
                /** @var \LaborDigital\Typo3FrontendApi\Site\Configuration\SiteLinkProviderInterface $provider */
                $provider = $context->getInstanceOf($linkProvider);
                $provider->provideLinks($this, $linkService);
            }

            // Transform the links
            $transformer = $context->TransformerFactory()->getTransformer();
            $result      = [];
            foreach ($this->links as $k => $link) {
                $result[$k] = $transformer->transform($link)['value'];
            }
            $this->links = [];

            return $result;
        }, [
            'tags'         => ['pageLinks'],
            'keyGenerator' => $context->getInstanceWithoutDi(ArrayBasedCacheKeyGenerator::class, [
                [
                    __CLASS__,
                    $this->pid,
                ],
            ]),
        ]);
    }

}
