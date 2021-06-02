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


namespace LaborDigital\T3fa\ExtConfigHandler\Api\Routing;


use InvalidArgumentException;
use Neunerlei\Inflection\Inflector;
use Neunerlei\Options\Options;
use Neunerlei\PathUtil\Path;
use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareConfigTrait
{
    /**
     * Internal helper to register a new middleware on the given list
     *
     * @param   array   $list             The list to add the middleware to
     * @param   string  $middlewareClass  The middleware class to register
     * @param   array   $options          Additional options for this middleware
     *                                    - identifier string: By default the middleware identifier is calculated
     *                                    based on the class name. If you set this you can overwrite the default.
     *                                    - before array|string: A list of or a single, middleware identifier to
     *                                    place this middleware in front of
     *                                    - after array|string: A list of or a single, middleware identifier to
     *                                    place this middleware after
     *
     * @return $this
     */
    protected function addMiddlewareToList(array &$list, string $middlewareClass, array $options): self
    {
        if (! class_exists($middlewareClass)) {
            throw new InvalidArgumentException('The given middleware class: ' . $middlewareClass . ' does not exist!');
        }
        
        if (! in_array(MiddlewareInterface::class, class_implements($middlewareClass), true)) {
            throw new InvalidArgumentException(
                'The given middleware class: ' . $middlewareClass
                . ' does not implement the required interface: ' . MiddlewareInterface::class . '!');
        }
        
        $beforeAfterDefinition = [
            'type' => 'array',
            'default' => [],
            'preFilter' => static function ($v) { return is_string($v) ? [$v] : $v; },
        ];
        
        $options = Options::make($options, [
            'identifier' => [
                'type' => 'string',
                'default' => function () use ($middlewareClass) {
                    return $this->makeMiddlewareIdentifier($middlewareClass);
                },
            ],
            'before' => $beforeAfterDefinition,
            'after' => $beforeAfterDefinition,
        ]);
        
        $identifier = $options['identifier'];
        
        $list[$identifier] = [
            'target' => $middlewareClass,
            'before' => $options['before'],
            'after' => $options['after'],
        ];
        
        return $this;
    }
    
    /**
     * Removes a previously registered middleware from the given list.
     *
     * @param   array   $list               The list to remove the middleware from
     * @param   string  $classOrIdentifier  The middleware identifier or class name to remove
     *
     * @return $this
     */
    protected function removeMiddlewareFromList(array &$list, string $classOrIdentifier): self
    {
        $cleanList = [];
        foreach ($list as $identifier => $config) {
            if ($identifier !== $classOrIdentifier && isset($list[$identifier]['target']) && $list[$identifier]['target'] !== $classOrIdentifier) {
                $cleanList[$identifier] = $config;
            }
        }
        $list = $cleanList;
        
        return $this;
    }
    
    /**
     * Checks if the given list contains a middleware with the given class or identifier
     *
     * @param   array   $list               The list to check for the middleware
     * @param   string  $classOrIdentifier  The middleware identifier or class name to check for
     *
     * @return bool
     */
    protected function hasMiddlewareInList(array $list, string $classOrIdentifier): bool
    {
        if (isset($list[$classOrIdentifier])) {
            return true;
        }
        
        foreach ($list as $identifier => $config) {
            if (isset($list[$identifier]['target']) && $list[$identifier]['target'] === $classOrIdentifier) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Builds an automatic middleware identifier out of the given class name and the extension key
     *
     * @param   string  $className  The name of the class to generate the middleware identifier for
     *
     * @return string
     */
    protected function makeMiddlewareIdentifier(string $className): string
    {
        return implode(
            '/',
            [
                Inflector::toDashed(
                    isset($this->namespace) ? $this->namespace : $this->context->getExtKeyWithVendor()),
                Inflector::toDashed(Path::classBasename($className)),
                md5($className),
            ]
        );
    }
}