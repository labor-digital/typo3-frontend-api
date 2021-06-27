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
 * Last modified: 2021.06.24 at 13:46
 */

declare(strict_types=1);


namespace LaborDigital\T3fa\ExtConfigHandler\Imaging;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractExtConfigConfigurator;
use LaborDigital\T3ba\Tool\Fal\ResizedImageOptionsTrait;
use LaborDigital\T3fa\Core\Imaging\Processor\CoreImagingProcessor;
use Neunerlei\Configuration\Loader\ConfigContext;

class ImagingConfigurator extends AbstractExtConfigConfigurator
{
    use ResizedImageOptionsTrait;
    
    /**
     * True if the image urls should point to the imaging endpoint
     *
     * @var bool
     */
    protected $enabled = true;
    
    /**
     * The list of registered image definitions the frontend may request
     *
     * @var array
     */
    protected $definitions = [];
    
    /**
     * The processor handles the image processing based on the processing definition.
     * By default the TYPO3 core image processor is used to convert the images using imagik or graphicsMagic.
     *
     * @var string
     */
    protected $imagingProcessor = CoreImagingProcessor::class;
    
    /**
     * Optional, additional options to be passed to the
     * webP converter implementation (rosell-dk/webp-convert). See the link below for possible options
     *
     * @var array
     * @see https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/options.md
     */
    protected $webpConverterOptions = [];
    
    /**
     * @inheritDoc
     */
    public function setConfigContext(ConfigContext $context): void
    {
        parent::setConfigContext($context);
        $this->registerDefinition('default', []);
    }
    
    
    /**
     * Returns true if image urls should point to the imaging endpoint,
     * false if the default TYPO3 image urls are returned
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    
    /**
     * Enables the imaging endpoint (The endpoint is enabled by default)
     *
     * @return $this
     */
    public function enable(): self
    {
        $this->enabled = true;
        
        return $this;
    }
    
    /**
     * Disables the imaging endpoint
     *
     * @return $this
     */
    public function disable(): self
    {
        $this->enabled = false;
        
        return $this;
    }
    
    /**
     * Registers a new processing definition for the imaging endpoint. The identifier can be used in the
     * /imaging-api endpoint using the ?definition=$identifier option to tell the server which image size you
     * want to have served. You can also set a default cropping definition
     * which can be overwritten by the request, too.
     *
     * The definition is an array of options like you would when you use getResizedFile() on the FalService
     *
     * NOTE: "default" is a special identifier. It applies to ALL images that don't have a specific definition key given!
     *
     * @param   string  $identifier  The unique identifier that can be provided to the imaging endpoint
     * @param   array   $options     The processing options to apply when an image is required
     *                               - width int|string: see *1
     *                               - height int|string: see *1
     *                               - minWidth int The minimal width of the image in pixels
     *                               - minHeight int The minimal height of the image in pixels
     *                               - maxWidth int The maximal width of the image in pixels
     *                               - maxHeight int The maximal height of the image in pixels
     *                               - crop bool|string|array: True if the image should be cropped instead of stretched
     *                               Can also be the name of a cropVariant that should be rendered
     *                               Can be an array with (x,y,width,height) keys to provide a custom crop mask
     *                               Can be overwritten using the "crop" GET parameter on the endpoint
     *                               - params string: Additional command line parameters for imagick
     *                               see: https://imagemagick.org/script/command-line-options.php
     *
     * *1: A numeric value, can also be a simple calculation. For further details take a look at imageResource.width:
     * https://docs.typo3.org/m/typo3/reference-typoscript/8.7/en-us/Functions/Imgresource/Index.html
     *
     * @return $this
     */
    public function registerDefinition(string $identifier, array $options): self
    {
        $this->definitions[$this->context->replaceMarkers($identifier)]
            = $this->applyResizedImageOptions($this->context->replaceMarkers($options));
        
        return $this;
    }
    
    /**
     * Removes a previously registered imaging definition from the list
     *
     * @param   string  $identifier
     *
     * @return $this
     */
    public function removeDefinition(string $identifier): self
    {
        if ($identifier === 'default') {
            throw new \InvalidArgumentException('You can\'t remove the "default" definition');
        }
        
        unset($this->definitions[$this->context->replaceMarkers($identifier)]);
        
        return $this;
    }
    
    /**
     * Returns the list of all registered imaging processing definitions.
     *
     * @return array
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }
    
    /**
     * Returns the configured image processor class name
     *
     * @return string
     */
    public function getImagingProcessor(): string
    {
        return $this->imagingProcessor;
    }
    
    /**
     * The processor handles the image processing based on the processing definition.
     * By default the TYPO3 core image processor is used to convert the images using imagik or graphicsMagic.
     * You can create your own implementation if you want to use an external service, like a AWS lambda function
     * to do the processing in an external task.
     *
     * @param   string  $imagingProcessor
     */
    public function setImagingProcessor(string $imagingProcessor): void
    {
        $this->imagingProcessor = $imagingProcessor;
    }
    
    /**
     * Returns the configured, additional options for the webP converter implementation
     *
     * @return array
     */
    public function getWebpConverterOptions(): array
    {
        return $this->webpConverterOptions;
    }
    
    /**
     * Sets optional, additional options to be passed to the
     * webP converter implementation (rosell-dk/webp-convert). See the link below for possible options
     *
     * NOTE: This is used in the CoreImagingProcessor. Other processors do not necessarily use this option.
     *
     * @param   array  $webpConverterOptions
     *
     * @see https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/options.md
     */
    public function setWebpConverterOptions(array $webpConverterOptions): void
    {
        $this->webpConverterOptions = $webpConverterOptions;
    }
}
