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
 * Last modified: 2020.10.28 at 21:05
 */

declare(strict_types=1);


namespace LaborDigital\Typo3FrontendApi\Cache\Metrics;


use LaborDigital\Typo3BetterApi\Container\ContainerAwareTrait;
use LaborDigital\Typo3FrontendApi\Cache\KeyGeneration\EnvironmentCacheKeyGenerator;

class MetricsRenderer
{
    use ContainerAwareTrait;

    /**
     * Renders the collected metrics into a semi-formatted string
     *
     * @param   \LaborDigital\Typo3FrontendApi\Cache\Metrics\MetricsTracker  $tracker
     *
     * @return string
     */
    public function render(MetricsTracker $tracker): string
    {
        $output = [];

        $output[] = 'BASE CACHE KEY: ' . $this->getInstanceOf(EnvironmentCacheKeyGenerator::class)->makeCacheKey();
        $output[] = '';

        foreach ($tracker->getAll() as $node) {
            $output[] = $this->renderNode($node, 0);
        }

        return implode(PHP_EOL, $output);
    }

    /**
     * Renders a single metrics node as a plain string output.
     * The children of the node will be rendered recursively as well
     *
     * @param   array  $node
     * @param   int    $level
     *
     * @return string
     */
    protected function renderNode(array $node, int $level): string
    {
        $output   = [];
        $prefix   = str_repeat('|   ', $level + 1);
        $output[] = $prefix . '[' . $node['type'] . ']: ' . $node['key'] .
                    (isset($node['time']) ? ' | TIME: ' . $node['time'] : '');
        $output[] = $prefix . str_repeat('-', 150);
        $output[] = $prefix;

        if (isset($node['generator'])) {
            $output[] = $prefix . '> GENERATOR: ' . $node['generator'];
        }

        if (! empty($node['tags'])) {
            sort($node['tags']);

            $lines = [];
            $line  = '';
            foreach ($node['tags'] as $tag) {
                $line .= ($line === '' ? '' : ', ') . $tag;
                if (strlen($line) > 150) {
                    $lines[] = $line;
                    $line    = '';
                }
            }
            if ($line !== '') {
                $lines[] = $line;
            }

            $output[] = $prefix . '> TAGS: ' . array_shift($lines);

            foreach ($lines as $line) {
                $output[] = $prefix . '>       ' . $line;
            }
        }

        $output[] = $prefix;

        if (! empty($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as $child) {
                $output[] = $this->renderNode($child, $level + 1);
            }
            $output[] = $prefix;
        }

        return implode(PHP_EOL, $output);
    }
}
