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
 * Last modified: 2019.09.17 at 17:29
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement;


use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Http\ImmediateResponseException;

/**
 * Class SpaContentPreparedException
 *
 * @package LaborDigital\Typo3FrontendApi\ContentElement
 *
 * This exception extends the ImmediateResponseException, because in that way we will
 * bypass the TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler completely
 * without having to deal with the default error handling.
 */
class SpaContentPreparedException extends ImmediateResponseException
{
    /**
     * The instance of the prepared content element
     *
     * @var ContentElement
     */
    protected $contentElement;

    /**
     * Returns the instance of the prepared content element
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement
     */
    public function getContentElement(): ContentElement
    {
        return $this->contentElement;
    }

    /**
     * Creates a new instance of myself ready to be thrown
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity\ContentElement  $contentElement
     *
     * @return static
     */
    public static function makeInstance(ContentElement $contentElement): self
    {
        $responseFactory      = TypoContainer::getInstance()->get(ResponseFactoryInterface::class);
        $self                 = new self($responseFactory->createResponse());
        $self->contentElement = $contentElement;

        return $self;
    }
}
