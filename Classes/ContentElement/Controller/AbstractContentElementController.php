<?php
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
 * Last modified: 2019.08.29 at 07:30
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\Controller;


use LaborDigital\Typo3BetterApi\Container\CommonDependencyTrait;
use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3FrontendApi\Cache\CacheServiceAwareTrait;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurationInterface;
use LaborDigital\Typo3FrontendApi\Shared\FrontendApiContextAwareTrait;

abstract class AbstractContentElementController implements ContentElementControllerInterface, ContentElementConfigurationInterface
{
    use CommonServiceLocatorTrait;
    use CommonDependencyTrait {
        CommonDependencyTrait::getInstanceOf insteadof CommonServiceLocatorTrait;
        CommonDependencyTrait::injectContainer insteadof CommonServiceLocatorTrait;
    }
    use FrontendApiContextAwareTrait;
    use CacheServiceAwareTrait;
}
