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
 * Last modified: 2019.11.03 at 18:14
 */

namespace LaborDigital\Typo3FrontendApi\Shared\Adapter;


use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\ErrorHandler\ErrorHandlerInterface;
use TYPO3Fluid\Fluid\Core\Parser\TemplateParser;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3Fluid\Fluid\View\TemplatePaths;

class DummyRenderingContext implements RenderingContextInterface {
	/**
	 * @inheritDoc
	 */
	public function getErrorHandler() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setErrorHandler(ErrorHandlerInterface $errorHandler) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setVariableProvider(VariableProviderInterface $variableProvider) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setViewHelperVariableContainer(ViewHelperVariableContainer $viewHelperVariableContainer) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getVariableProvider() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getViewHelperVariableContainer() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getViewHelperResolver() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setViewHelperResolver(ViewHelperResolver $viewHelperResolver) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getViewHelperInvoker() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setViewHelperInvoker(ViewHelperInvoker $viewHelperInvoker) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setTemplateParser(TemplateParser $templateParser) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTemplateParser() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setTemplateCompiler(TemplateCompiler $templateCompiler) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTemplateCompiler() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTemplatePaths() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setTemplatePaths(TemplatePaths $templatePaths) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setCache(FluidCacheInterface $cache) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getCache() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function isCacheEnabled() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setTemplateProcessors(array $templateProcessors) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTemplateProcessors() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getExpressionNodeTypes() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setExpressionNodeTypes(array $expressionNodeTypes) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function buildParserConfiguration() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getControllerName() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setControllerName($controllerName) {
	}
	
	/**
	 * @inheritDoc
	 */
	public function getControllerAction() {
	}
	
	/**
	 * @inheritDoc
	 */
	public function setControllerAction($action) {
	}
	
}