<?php


namespace LaborDigital\Typo3FrontendApi\ContentElement\Configuration;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;

interface ContentElementConfigurationInterface {
	
	/**
	 * Is used to configure the static content element information, like a title, description,
	 * the controller class and similar information.
	 *
	 * The configuration is cached, so this method is not called every time the element is required.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator $configurator
	 * @param \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext                                $context
	 *
	 * @return void
	 */
	public static function configureElement(ContentElementConfigurator $configurator, ExtConfigContext $context): void;
}