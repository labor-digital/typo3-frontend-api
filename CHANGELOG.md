# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [9.2.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.1.1...v9.2.0) (2020-03-25)


### Features

* **CommonElement:** add common custom element for dynamic elements based on a registered class ([254d084](https://github.com/labor-digital/typo3-frontend-api/commit/254d084799a7084dd1dff50d5c3a848d2ad55aff))
* add support for menu post processors to filter generated menu arrays ([cb3a1ea](https://github.com/labor-digital/typo3-frontend-api/commit/cb3a1eacb6ff9c40f4bcc669c071d63dfb564593))
* **Site:** add support for root line data provider for dynamic data generation ([0e27da6](https://github.com/labor-digital/typo3-frontend-api/commit/0e27da6e6374f51567d22ef5cddbd05f4a7c05d6))


### Bug Fixes

* add missing default value for $render in SiteMenuPreProcessorEvent ([cba4118](https://github.com/labor-digital/typo3-frontend-api/commit/cba4118671e5b9b9b0c937c6562b0ac86c1da029))
* make sure the name of registerResourcesDirectory() is consistent ([efc5430](https://github.com/labor-digital/typo3-frontend-api/commit/efc5430fb3d5e857ef1151253f48659f9f8e6093))
* remove debug fractal "fooPreset" ([2fc80d0](https://github.com/labor-digital/typo3-frontend-api/commit/2fc80d0b29ced270a8f4401f834ac16de97d7028))

### [9.1.1](https://github.com/labor-digital/typo3-frontend-api/compare/v9.1.0...v9.1.1) (2020-03-23)


### Bug Fixes

* add the correct vendor name and sorting options back ([8ef7bc6](https://github.com/labor-digital/typo3-frontend-api/commit/8ef7bc683a4e82bebe23d5254377294cc6b41600))

## 9.1.0 (2020-03-23)


### Features

* first public release ([26fb8bd](https://github.com/labor-digital/typo3-frontend-api/commit/26fb8bd89354c25e214f586cab290c1ee1527d7b))

## [3.2.1] (2020-02-04)


### Bug Fixes

* **CacheMiddleware:** force-update the cache entry if the user is allowed to override the cache state by cache-control headers ([15b5bfe])
* **FrontendApiToolOption:** replace Env service lookup with env aspect lookup ([3d39122])
* **SchedulerController:** fix scheduler controller incompatibilities with typo3 v9 ([2a5a04f])



# [3.2.0] (2020-01-31)


### Features

* **CacheMiddleware:** handle cache disabling in the cache middleware and not in the cache implementation ([bec2769])
* **SimpleTokenAuthControllerTrait:** add an option to disable the token authentication in dev mode ([d96afa7])



# [3.1.0] (2020-01-23)


### Features

* implement scheduler endpoint and tool configuration ([0af1087])



## [3.0.1] (2020-01-20)


### Bug Fixes

* **VirtualColumns:** allow virtual columns to be empty strings ([00dc708])



# [3.0.0] (2020-01-20)


### Bug Fixes

* **ContentElement:** always handle the "data" and "children" properties in the content element definition as objects and never as array ([0aded0d])


### Code Refactoring

* split up the configuration object into multiple classes ([6eec306])


### BREAKING CHANGES

* configuration interface changed



# [2.3.0] (2020-01-10)


### Features

* merge in some features and fixes that I did on the v8/v7 branch ([a8790c1])



# [2.2.0] (2019-12-09)


### Features

* **CacheHandler:** make sure to add the tag to the cache entries for the frontend api responses ([ac05915])



# [2.1.0] (2019-12-06)


### Bug Fixes

* **CacheMiddleware:** set the default cache lifetime of the cache to 15 minutes and set the "expires" header accordingly ([bf267cb])
* **FileTransformer:** silently handle exceptions while transforming files ([dfbae30])


### Features

* **CacheHandler:** implement a more reliable cache handling that automatically purges the stored data if records are changed ([76e65eb])
* **ContentElement:** implement cType dropdown section selection for content elements ([6a7bcdb])
* adjust search and index integration to the latest version ([3c2ad00])



# [2.0.0] (2019-12-02)


* feat: ([3c1b7d2])


### BREAKING CHANGES

* set the version number to v2



# [0.17.0] (2019-12-02)


### Features

* **ContentElement:** add field default configuration to the content element field defaults ([2d7c68a])



# [0.16.0] (2019-11-28)


### Bug Fixes

* make sure that include=* works even if there are nested, other includes ([79b4598])
* **ResourceDataRepository:** make sure the query array is also added to single-element requests ([85f80e4])


### Features

* **ContentElement:** add transformer options to content element data. Currently you can also request all includes of the transformed data elements like you would with the autoTransform method() ([f129d8b])
* **Transformation:** make sure object storage objects end up as array and not as object ([0f64afe])



# [0.15.0] (2019-11-27)


### Features

* remove EVENT_ prefix in ExtConfigOptionEventList class ([7d06d20])



# [0.14.0] (2019-11-27)


### Bug Fixes

* **CacheMiddleware:** temporarily fix api caching issues by lowering the ttl to 15 minutes until I find a better caching solution ([1d5a041])
* **Transformation:** better handling for internal values and simple objects like DateTime ([3ed08fc])


### Features

* remove the redundant EVENT_ prefix for all EventList interfaces ([8171f11])
* **ContentElement:** automatically build single entity request for the initial state query when the query contains a "id" key ([9c46204])
* **FrontendSimulationMiddleware:** make sure that the route enhancer query parameters get merged back into the api request object ([68b00d8])
* **Pagination:** make sure an exception is thrown if an array is given that is neither a array list nor a sequential array ([1042eb7])
* **Transformation:** add more automatic transformation options ([92ae62d])



# [0.13.0] (2019-11-21)


### Bug Fixes

* **ErrorHandler:** make sure errors will be passed through to the root error handler when nested api calls occur ([2dbce3a])


### Features

* **ApiMiddlewareFork:** make sure that all headers are present in the server request for api calls ([5cb0fba])



# [0.12.0] (2019-11-16)


### Features

* **Whoops:** add typo3 error event as well as a error response filter event to the error handler class ([b5b8edf])



# [0.11.0] (2019-11-11)


### Features

* **CacheMiddleware:** disable caching of frontend contents for logged in users ([6e2b8db])



# [0.10.0] (2019-11-11)


### Bug Fixes

* **CacheHandler:** use page based cache instead of the frontend cache to take the tsfe hash into account when serving cached entries ([e84fdfc])
* **ContentElementHandler:** rewrite the virtual columns in the TCA column section when executing the content element controller functions to avoid issues when resolving file references ([a6746ef])
* **JsonApi:** fix issue where the common elements were not correctly retrieved on a "non-default" layout, if they were not specifically defined for a layout key ([6cb8012])
* **Transformation:** fix detecting scalar values as circular values when using auto-transform ([f13fb0e])
* **Transformation:** fix issues where tracked values where not reset correctly ([3462672])


### Features

* **Transformation:** even better detection of circular objects when transforming them. The script now throw an error instead of returning the "CIRCULAR" string ([3226919])



# [0.9.0] (2019-11-08)


### Features

* **ErrorHandler:** remove Kint as var dumper and use extbase var dumper instead ([bec05b7])
* **SysFileController:** allow handling for processed files as well ([fe45b5c])
* **Transformation:** implement auto transform context to avoid never ending recursion loops when circular reference objects are transformed ([eccc87a])
* **TransformerFactory:** implement check to get the correct transformer config for non-sequential arrays ([d5e24c5])



# [0.8.0] (2019-11-08)


### Bug Fixes

* **ContentElement:** fix issue where the spa rendering of content elements caused the frontend controller to fail ([eb5a668])
* **ContentElement:** fix issue where virtual columns did not get their default values ([076d8a8])


### Features

* add option to register directory of content elements ([ecc9ac5])
* **ContentElement:** add css class handling and automatic parsing of typo3 links ([0dc1dcf])
* **ContentElement:** add option to overwrite existing TYPO3 content element's with an API element controller ([c8a5246])
* **Page:** add additional fields to generated root line and cacheless data map factory to hydrate page models without issues ([63b379d])
* **PageTranslation:** cache translation results and convert sprintf %s formatting for javascript ([81ca49b])



## [0.7.2] (2019-10-28)


### Bug Fixes

* **FrontendSimulationMiddleware:** rewrite subRequest instead of the main request object to avoid notFound exceptions ([95e5b5b])



## [0.7.1] (2019-10-28)


### Bug Fixes

* **ContentElementHandler:** make sure to bypass the production content element error handler ([54b4bb9])



# [0.7.0] (2019-10-28)


### Features

* **FrontendSimulationMiddleware:** fetch the correct site, even if an incorrect scheme was passed ([543d6d0])



# [0.6.0] (2019-10-28)


### Features

* show speaking error handler if an admin user is logged in and no referrer is present ([412f829])



## [0.5.1] (2019-10-27)


### Bug Fixes

* fix broken frontend api option when a new extension is installed in the backend ([780d6ca])



# [0.5.0] (2019-10-25)


### Bug Fixes

* minor adjustments of readability ([d830385])


### Features

* refactor the json api builtin objects to be more intuitive than before ([6dc07dd])
* **ApiMiddlewareFork:** add support for a generic, static template to be rendered when the request is not handled by the api ([577681f])
* **ContentElement:** add support for backend preview rendering ([bca856b])
* **ContentElement:** add support for the new content element class instead of the old document array ([619dd6a])
* **DefaultContentElementModel:** add magic method to get all properties of the default content element model ([626f321])
* **ErrorHandler:** allow all cors requests in the development environment ([6fa776c])
* **SiteConfig:** add better integration for the site config definition ([0acee0b])



# [0.4.0] (2019-09-25)


### Features

* implement changes vor TYPO3 v9 + full single page API for sites ([92fbf36])



# [0.3.0] (2019-09-07)


### Bug Fixes

* **FrontendApiOption:** Break when the cached element config is somehow false... ([72dfa62])
* fix handleLink() breaking when called on a non-GET request ([a8ca58c])


### Features

* implement better api change: rename TableConfigInterface->configure to configureTable and make it static ([1197b90])



# [0.2.0] (2019-09-06)


### Features

* finalize implementation ([12ba897])
* initial source commit ([ec07674])



## [0.1.1] (2019-08-12)



# 0.1.0 (2019-08-06)


### Features

* initial release ([6e1dcba])
