# Tools
In addition to the core features, the frontend API extension comes with a set of additional tools you can activate using your ext config file.

All tool configuration options can be found under ```$configurator->frontendApi()->tool()```

## Up endpoint
The up endpoint is a fairly simple addition; it registers a new route at ```/api/up```, which returns "OK" and a state of 200 if your system is running as desired.
You can use this as a health or uptime check. There are no other options for this endpoint.

```php
<?php
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\OptionList\ExtConfigOptionList;

class MyExtConfig implements ExtConfigInterface {
    public function configure(ExtConfigOptionList $configurator,ExtConfigContext $context){
        // When you configure the route it will be auto-enabled
        $configurator->frontendApi()->tool()->configureUpRoute();
    }
}
```

## Scheduler endpoint
When you are working with the [scheduler](https://docs.typo3.org/c/typo3/cms-scheduler/master/en-us/) TYPO3 core extension, you can either run a task using the CLI or manually in the backend.
There is no built-in way to run a task / to trigger the scheduler cronjob via HTTP (as far as I know).

If you enable the scheduler endpoint AND have the scheduler extension installed, you can do both using your frontend API.

The endpoint is accessible on ```/api/scheduler/run``` to run the whole scheduler task list
If you provide the id of a given task like ```/api/scheduler/run/1``` for task ID 1, you can also execute a single task.
While you are running in a Dev environment and execute a single task it will always be forced to run, ignoring the cronjob configuration;
can be used to debug your scheduler tasks locally.

Currently, it is not possible to force a scheduler task to run in the production environment.

The token is used as a "password" to access the scheduler endpoint only for authenticated users.
The token can either be received using the [Authentication Bearer header](https://tools.ietf.org/html/rfc6750) or via query parameter "token" when the "allowTokenInQuery" option is enabled

::: details Arguments
- **$token** Defines either a single or multiple tokens that act as "password" to access the scheduler endpoint. 
The token can either be received using the Authentication Bearer header or via query parameter "token," when enabled by setting "allowTokenInQuery" to true
- **$options** The options to configure the scheduler execution
    - **enabled** bool (TRUE): True by default, enables the endpoint,
    setting this to false disables it after it was previously enabled.
    - **maxExecutionType** int (60*10): The number in seconds the php script
    can run before it is forcefully killed by the server.
    - **allowTokenInQuery** bool (FALSE): If set to true the token may be passed by query
    parameter instead of a HTTP header. This is TRUE by default if you are running in a dev environment.
:::

```php
<?php
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\OptionList\ExtConfigOptionList;

class MyExtConfig implements ExtConfigInterface {
    public function configure(ExtConfigOptionList $configurator,ExtConfigContext $context){
        // Simple configuration
        $configurator->frontendApi()->tool()->configureSchedulerRoute("A_STRONG_TOKEN");
    
        // Configuration with additional options
        $configurator->frontendApi()->tool()->configureSchedulerRoute("A_STRONG_TOKEN", [
            "maxExecutionType" => 60 * 15
        ]);
    }
}
```

## Imaging
One major problem when you work with an API based frontend is that you lose access to all view-helpers. 
Therefore the file/fileReference resources for images contain a list of all possible crop variants of the resource.
The generation of resized images for thumbnails or retina support a feature of the default API result.

Imaging tries to solve this problem by providing a particular endpoint for image processing. The endpoint is not part of the default ```/api/``` routing to avoid additional overhead.
Instead, the endpoint is a separate PHP file you find at ```/fileadmin/imaging.php```. If the entry point file does not exist yet, clear the system caches in the backend and reload the page once.

### Imaging Configuration
The endpoint is not active by itself and requires to be configured. You can configure the endpoint in your extConfig.

To configure the endpoint, you have to provide a list of processing definitions for the
size of the image. Each definition has a unique key. The key can be used in the imaging.php endpoint
to tell the server which image size you want to have served. You can also set a default cropping definition, which can be overwritten by request to imaging.php, too.

The definition of the sets is done as an array with the $key as the unique key of the definition
and the $value as an array containing an imaging definition like you would when you use getResizedFile()
on the FalFileService!

::: tip 
"default" is a special key. It applies to ALL images that don't have a specific definition key given!
::: 

::: details Arguments
- $definitions A list of key => value pairs to define the imaging definitions
Each value array can contain the following keys:
    - **width** int|string: see *1
    - **height** int|string: see *1
    - **minWidth** int|string: see *1
    - **minHeight** int|string: see *1
    - **maxWidth** int|string: see *1
    - **maxHeight** int|string: see *1
    - **crop** bool|string (FALSE): True if the image should be cropped instead of stretched
    Can also be the name of a cropVariant that should be rendered
    Can be overwritten using the "crop" GET parameter on the endpoint
- $options Additional options for the imaging endpoint
    - **redirectDirectoryPath** string: defines the directory
    where the redirect information is stored (not the original image files!).
    DEFAULT: The default path to the var directory based on your TYPO3 config /imaging
    - **endpointDirectoryPath** string: The directory where the imaging.php entry point should be compiled. The directory has to be writable by the webserver!
    DEFAULT: The default fileadmin directory inside your public folder
    - **imagingProvider** string: By default, the processing is done by the TYPO3 core; if you want to use another provider like s3 as a backend, you can create a custom imaging provider. The given value is the class name of your provider.
    The class has to implement the ImagingProviderInterface!
    DEFAULT: LaborDigital\Typo3FrontendApi\Imaging\Provider\CoreImagingProvider
    - **webPConverterOptions** array: Optional, additional options to be passed to the
    webP converter implementation (rosell-dk/webp-convert). See the link below for possible options
    
*1: A numeric value, can end a "c" to crop the image to the target width
:::
```php
<?php
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\OptionList\ExtConfigOptionList;

class MyExtConfig implements ExtConfigInterface {
    public function configure(ExtConfigOptionList $configurator,ExtConfigContext $context){
        // Simple configuration
        $configurator->frontendApi()->tool()->configureImaging([
            "definitionKey" => ["maxWidth" => 1200]
        ]);
    
        // Configuration with additional options
        $configurator->frontendApi()->tool()->configureImaging([/* ... */], [
            "webPConverterOptions" => ["jpg" => ["quality" => 80]]
        ]);
    }
}
```

### What Imaging does
After you registered, your configuration imaging will automatically change all URLs that are returned for image files when they are served as an image resource.
Instead of the real image URL, the new path will point to something similar to ```/fileadmin/imaging.php?file=name-of-your-file.haaaaaaaaaaash.r12.jpg```

Yes, the initial request to the imaging.php is still a PHP file, meaning for every image, you will run a PHP script. 
However, the entry point is streamlined to cause only minimal overhead. For that reason, the entry-point will not serve the image itself, but redirect the request to the correct URL instead.
Before it redirects the request it does the following:

* A resized image version is generated based on the given definition or the default definition
* Optionally a cropped version is generated if defined in the definition or the crop parameter
* If a png/jpg (or a cropped version of any other type!) is served, a .wepb image is auto-generated
* Detect if the current client supports .webp and serve the .webp format if required

After the base actions, the request is redirected to a static file. For all browsers that use intelligent caching,
all subsequent requests will then be sent to the static file instead of the PHP file. Meaning we only have the PHP overhead for the first request.

### How to use Imaging
The basic usage of Imaging is simple: Use the new URL instead of your image source.

* To apply a definition configured in your ext config, append the "&definition=DEFINITION_KEY" parameter to the URL.
You can create any definition key for every image file served by Imaging.
* If you want to crop the image to a format that is not part of your definition, you can pass the "&crop=CROP_VARIANT" parameter to the URL.
    * If the requested image does not have the given variant, the original image dimensions are used.
    * To disable the cropping for a definition that defines a crop variant set "crop" to "none".

### Caching
All generated redirects are cached as files in the ```/var/imaging``` directory. To reset all generated redirects, clear your "global TYPO3 cache".
 
### Creating custom imaging providers
By default, Imaging uses TYPO3's core image manipulation capabilities and processed file storage.
It is also possible to create a custom implementation of an ImagingProvider class if you want to use AWS S3 or want to redirect
your assets to a CDN before they are delivered. 

To create a new imaging provider create a new class, that implements the ImagingProviderInterface or extends the AbstractImagingProvider class:
```php
<?php
namespace LaborDigital\Typo3FrontendApi\Imaging\Provider;

use LaborDigital\Typo3BetterApi\FileAndFolder\FileInfo\FileInfo;
use LaborDigital\Typo3FrontendApi\Imaging\ImagingContext;

class MyImagingProvider extends AbstractImagingProvider {
    public function process(array $definition, FileInfo $fileInfo, ImagingContext $context) : void{
        // Do your manipulation here

        // Set the path to redirect the request here:
        $this->defaultRedirect = "/path/to/resource.jpg";

        // Optional: If you want to serve a webP if possible,
        // you can set the webp redirect explicitly. If you don't want webp support
        // set this to NULL instead.
        $this->webPRedirect = "/path/to/resource.webp";

        // INFO: Path's that start with a slash (/) automatically 
        // get the current host name prepended.
    }
}
```
