<?php


namespace LaborDigital\Typo3FrontendApi\ContentElement\Controller;


interface ContentElementControllerInterface {
	
	/**
	 * Should prepare the given context object when the element is displayed in the frontend.
	 * If a STRING is returned the string is used as rendered view and displayed without further processing.
	 * If any other value is returned it is transformed using the configured resource transformer and then added as
	 * "data" to the generated json element.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext $context
	 *
	 * @return mixed
	 */
	public function handle(ContentElementControllerContext $context);
	
	/**
	 * Is used to build the backend preview of this content element.
	 * Should always return a string that is used as preview.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext $context
	 *
	 * @return string
	 */
	public function handleBackend(ContentElementControllerContext $context): string;
	
}