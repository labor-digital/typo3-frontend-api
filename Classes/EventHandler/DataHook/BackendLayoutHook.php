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
 * Last modified: 2021.06.02 at 17:54
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\EventHandler\DataHook;


use LaborDigital\T3ba\Core\Di\PublicServiceInterface;
use LaborDigital\T3ba\Tool\DataHook\DataHookContext;
use LaborDigital\T3ba\Tool\Rendering\FlashMessageRenderingService;
use LaborDigital\T3ba\Tool\Translation\Translator;
use Neunerlei\Inflection\Inflector;

class BackendLayoutHook implements PublicServiceInterface
{
    /**
     * @var \LaborDigital\T3ba\Tool\Rendering\FlashMessageRenderingService
     */
    protected $flashMessages;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Translation\Translator
     */
    protected $translator;
    
    public function __construct(FlashMessageRenderingService $flashMessages, Translator $translator)
    {
        $this->flashMessages = $flashMessages;
        $this->translator = $translator;
    }
    
    public function saveHook(DataHookContext $context): void
    {
        $value = trim((string)$context->getData());
        if (empty($value)) {
            return;
        }
        
        $modified = Inflector::toCamelBack($value, true);
        if (strtolower($modified) !== strtolower($value)) {
            $context->setData($modified);
            $this->flashMessages->addInfo(
                $this->translator->translate(
                    't3fa.t.backendLayout.msg.identifierModified',
                    [$value, $modified]
                )
            );
        }
    }
}