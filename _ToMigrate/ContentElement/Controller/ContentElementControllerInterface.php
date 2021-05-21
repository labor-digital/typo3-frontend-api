<?php


namespace LaborDigital\Typo3FrontendApi\ContentElement\Controller;


interface ContentElementControllerInterface
{

    /**
     * Should prepare the given context object when the element is displayed in the frontend.
     * If a STRING is returned the string is used as rendered view and displayed without further processing.
     * If any other value is returned it is transformed using the configured resource transformer and then added as
     * "data" to the generated json element.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     *
     * @return mixed
     */
    public function handle(ContentElementControllerContext $context);

    /**
     * Is used to build the backend preview of this content element.
     * Should always return a string that is used as preview.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext  $context
     *
     * @return string
     */
    public function handleBackend(ContentElementControllerContext $context): string;

    /**
     * Allows the controller to handle errors that occurred while it generated some content.
     * This method receives the error/exception and should handle it somehow.
     *
     * If the method was able to handle the error by rendering a custom result a string should be returned.
     * If the global error handler should handle the error, just return null.
     *
     * @param   \Throwable                       $error     The error that should be handled
     * @param   ContentElementControllerContext  $context   The content element context while the error occurred
     * @param   bool                             $frontend  True if the request came from the frontend, false if it was a backend request
     *
     * @return string|null
     */
    public function handleError(\Throwable $error, ContentElementControllerContext $context, bool $frontend): ?string;

}
