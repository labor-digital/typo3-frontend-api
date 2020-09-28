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
 * Last modified: 2020.07.20 at 19:34
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
 * Last modified: 2019.12.10 at 20:07
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\Translation;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;
use Neunerlei\Arrays\Arrays;

abstract class AbstractTranslation
{
    use FrontendApiContextAwareTrait;

    /**
     * The identifier for the language this object represents
     *
     * @var mixed
     */
    protected $languageId;

    /**
     * AbstractTranslation constructor.
     *
     * @param   mixed  $languageId  Either the sys language uid or the two char iso code of a language
     */
    public function __construct($languageId)
    {
        $this->languageId = $languageId;
    }

    /**
     * Internal logic to translate the list of given labels and return a flattened array of all translations
     *
     * @param   array  $labels
     *
     * @return array
     */
    protected function getLabelTranslations(array $labels): array
    {
        $translations = [];
        if (! empty($labels)) {
            $translator = $this->FrontendApiContext()->Translation();
            foreach ($labels as $k => $label) {
                $labelClean = trim($translator->translate($label));
                // Convert %s sprintf formats to {0}... formats for js frameworks to cope with
                // @todo evaluate if this will cause issues ?
                $c                = 0;
                $labelClean       = preg_replace_callback('~%s~si', function () use (&$c) {
                    return '{' . $c++ . '}';
                }, $labelClean);
                $translations[$k] = $labelClean;
            }
        }


        // Finalize the translations
        $translations = Arrays::unflatten($translations);

        return $translations;
    }

    /**
     * Factory method to create a new instance of myself
     *
     * @param $languageId
     *
     * @return PageTranslation|HybridTranslation
     * @deprecated removed in v10 use the __construct method instead
     */
    public static function makeInstance($languageId): AbstractTranslation
    {
        return TypoContainer::getInstance()->get(static::class, ['args' => [$languageId]]);
    }
}
