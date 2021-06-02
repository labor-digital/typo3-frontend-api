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
 * Last modified: 2021.06.01 at 14:43
 */

declare(strict_types=1);

namespace LaborDigital\T3fa\Core\Cache\Metrics;

use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\Cache\KeyGenerator\EnvironmentCacheKeyGenerator;
use Neunerlei\TinyTimy\DateTimy;

class MetricsRenderer implements PublicServiceInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3BA\Tool\Cache\KeyGenerator\EnvironmentCacheKeyGenerator
     */
    protected $environmentCacheKeyGenerator;
    
    /**
     * @var \LaborDigital\T3fa\Core\Cache\Metrics\MetricsTracker
     */
    protected $metricsTracker;
    
    public function __construct(EnvironmentCacheKeyGenerator $environmentCacheKeyGenerator, MetricsTracker $metricsTracker)
    {
        $this->environmentCacheKeyGenerator = $environmentCacheKeyGenerator;
        $this->metricsTracker = $metricsTracker;
    }
    
    /**
     * Renders the collected metrics into a semi-formatted string
     *
     * @return string
     */
    public function render(): string
    {
        $output = [];
        
        $output[] = 'BASE CACHE KEY: ' . $this->environmentCacheKeyGenerator->makeCacheKey();
        $output[] = '';
        
        foreach ($this->metricsTracker->getAll() as $node) {
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
        $output = [];
        $prefix = str_repeat('|   ', $level + 1);
        $output[] = $prefix . '[' . $node['type'] . ']: ' . $node['key'] .
                    (isset($node['time']) ? ' | TIME: ' . $node['time'] : '') .
                    $this->renderGenerated($node) .
                    $this->renderLifetime($node);
        $output[] = $prefix . str_repeat('-', 160);
        $output[] = $prefix;
        
        if (isset($node['generator'])) {
            $output[] = $prefix . '> GENERATOR: ' . $node['generator'];
        }
        
        if (! empty($node['tags'])) {
            sort($node['tags']);
            
            $lines = [];
            $line = '';
            foreach ($node['tags'] as $tag) {
                $line .= ($line === '' ? '' : ', ') . $tag;
                if (strlen($line) > 160) {
                    $lines[] = $line;
                    $line = '';
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
    
    /**
     * Helper to render the generated timestamp as string
     *
     * @param   array  $node
     *
     * @return string
     */
    protected function renderGenerated(array $node): string
    {
        if (! isset($node['generated'])) {
            return '';
        }
        
        return ' | GENERATED: ' . (new DateTimy($node['generated']))->formatSql();
    }
    
    /**
     * Helper to render the valid until string
     *
     * @param   array  $node
     *
     * @return string
     */
    protected function renderLifetime(array $node): string
    {
        if (! isset($node['generated'], $node['lifetime'])) {
            return '';
        }
        
        $timestamp = (new DateTimy($node['lifetime'] + $node['generated']))->formatSql();
        $seconds = $node['lifetime'] . 'sec';
        
        return ' | VALID UNTIL: ' . $timestamp . ' - ' . $seconds;
    }
}
