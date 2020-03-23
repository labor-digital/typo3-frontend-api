<?php
/**
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
 * Last modified: 2020.03.20 at 19:16
 */

declare(strict_types=1);

namespace LaborDigital\Typo3FrontendApi\Event;

use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;

/**
 * Class ContentElementTableDefinitionFilterEvent
 *
 * Dispatched when the content element configuration is generated.
 * Allows to modify the tt_content table after it is merged back into the main array
 *
 * @package LaborDigital\Typo3FrontendApi\Event
 */
class ContentElementTableDefinitionFilterEvent {
	
	/**
	 * The tt_content tca table instance
	 * @var \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable
	 */
	protected $table;
	
	/**
	 * The ext config context
	 * @var \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
	 */
	protected $context;
	
	/**
	 * The list of all configurators that have been applied
	 * @var \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator[]
	 */
	protected $configurators;
	
	/**
	 * ContentElementTableDefinitionFilterEvent constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable $table
	 * @param \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext     $context
	 * @param array                                                       $configurators
	 */
	public function __construct(TcaTable $table, ExtConfigContext $context, array $configurators) {
		$this->table = $table;
		$this->context = $context;
		$this->configurators = $configurators;
	}
	
	/**
	 * Returns the tt_content tca table instance
	 * @return \LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable
	 */
	public function getTable(): TcaTable {
		return $this->table;
	}
	
	/**
	 * Returns the ext config context
	 * @return \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
	 */
	public function getContext(): ExtConfigContext {
		return $this->context;
	}
	
	/**
	 * Returns the list of all configurators that have been applied
	 * @return \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator[]
	 */
	public function getConfigurators(): array {
		return $this->configurators;
	}
}