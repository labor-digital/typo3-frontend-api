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
 * Last modified: 2019.08.13 at 14:56
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Controller;

use LaborDigital\Typo3BetterApi\Container\CommonServiceDependencyTrait;
use LaborDigital\Typo3BetterApi\Container\CommonServiceLocatorTrait;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\ResponseFactoryTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurationInterface;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\ResourceTransformerTrait;

abstract class AbstractResourceController implements ResourceControllerInterface, ResourceConfigurationInterface {
	use ResponseFactoryTrait;
	use CommonServiceLocatorTrait;
	use ResourceControllerQueryConstraintsTrait;
	use ResourceTransformerTrait;
	use CommonServiceDependencyTrait {
		CommonServiceDependencyTrait::getInstanceOf insteadof CommonServiceLocatorTrait;
		CommonServiceDependencyTrait::injectContainer insteadof CommonServiceLocatorTrait;
	}
	
}