# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

### [9.18.5](https://github.com/labor-digital/typo3-frontend-api/compare/v9.18.4...v9.18.5) (2020-07-09)


### Bug Fixes

* **Transformer:** pass $result to autoTransform() when using HybridSelfTransformingInterface ([6837acd](https://github.com/labor-digital/typo3-frontend-api/commit/6837acdf6f0f4c05dc712113ed5b34b80e159876))

### [9.18.4](https://github.com/labor-digital/typo3-frontend-api/compare/v9.18.3...v9.18.4) (2020-07-01)


### Bug Fixes

* **Transformer:** fix type mismatch for md5 ([6ac12a7](https://github.com/labor-digital/typo3-frontend-api/commit/6ac12a7bca4a829609a8cb1bb6d4598da9b27282))

### [9.18.3](https://github.com/labor-digital/typo3-frontend-api/compare/v9.18.2...v9.18.3) (2020-07-01)


### Bug Fixes

* **ContentElement:** make sure nested content element rendering does not break the SPA mode ([e9f25f7](https://github.com/labor-digital/typo3-frontend-api/commit/e9f25f7ece2b3ff34b9bac1bfdaab113602086b0))
* **TransformerConfigGenerator:** make sure simple extbase model properties are handled correctly ([c2e5403](https://github.com/labor-digital/typo3-frontend-api/commit/c2e54031139c754da9f353c2e20602715b59444d))

### [9.18.2](https://github.com/labor-digital/typo3-frontend-api/compare/v9.18.1...v9.18.2) (2020-06-22)


### Bug Fixes

* **ResourceDataResult:** make sure query in array result is not a string ([f4e80ee](https://github.com/labor-digital/typo3-frontend-api/commit/f4e80ee865b6c0ec8d138a60ec3d7a0daf62868e))

### [9.18.1](https://github.com/labor-digital/typo3-frontend-api/compare/v9.18.0...v9.18.1) (2020-06-18)


### Bug Fixes

* **SwitchableContentElementActionTrait:** make the type compatible with extbase plugin definition ([7b74f5d](https://github.com/labor-digital/typo3-frontend-api/commit/7b74f5d0f51d59d5215c596f5cf7aa970a18e2b4))

## [9.18.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.17.1...v9.18.0) (2020-05-28)


### Features

* **ContentElement:** implement SwitchableContentElementActionTrait ([bfa7c6f](https://github.com/labor-digital/typo3-frontend-api/commit/bfa7c6fe7706d2d9a88af2be41494c7d3bfdcfe7))


### Bug Fixes

* **AbstractResourceStrategy:** make sure resource arrays get their counts calculated correctly ([640be76](https://github.com/labor-digital/typo3-frontend-api/commit/640be769cc825a1923c2f2133125deffa2ab5d12))

### [9.17.1](https://github.com/labor-digital/typo3-frontend-api/compare/v9.17.0...v9.17.1) (2020-05-27)


### Bug Fixes

* **ContentElement:** make sure the initial state is correctly serialized ([862b3e2](https://github.com/labor-digital/typo3-frontend-api/commit/862b3e232cabb51ff7bddca3e887756bdcf1cb89))

## [9.17.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.16.1...v9.17.0) (2020-05-23)


### Features

* **JsonApi:** add better "additionalRoutes" and ResourceDataResult ([882eedb](https://github.com/labor-digital/typo3-frontend-api/commit/882eedb3d4b41155ee21d2b87c221b35b4786347))


### Bug Fixes

* **AdditionalRouteStrategy:** make sure the correct content-type is set ([02a4f37](https://github.com/labor-digital/typo3-frontend-api/commit/02a4f37c4051fc64f0565c2b86cd84b12710cef1))
* make sure the cache disabling header is provided correctly to the cache middleware ([03fca97](https://github.com/labor-digital/typo3-frontend-api/commit/03fca9781a9993e9eab6d7cb89f249f656882a2d))
* remove dev remnant ([bd6fa5f](https://github.com/labor-digital/typo3-frontend-api/commit/bd6fa5fa64b629ff9d36e615866d0aac1b188fe4))

### [9.16.1](https://github.com/labor-digital/typo3-frontend-api/compare/v9.16.0...v9.16.1) (2020-05-22)


### Bug Fixes

* **Pagination:** rename PageFinderAwareSelfPaginationInterface ([b4b24b9](https://github.com/labor-digital/typo3-frontend-api/commit/b4b24b99db9bff0cc224607ccc445aa866c655ae))

## [9.16.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.15.2...v9.16.0) (2020-05-20)


### Features

* **Pagination:** implement "SelfPaginatingInterface" to allow better pagination of external API objects or generic data ([3a6e4a7](https://github.com/labor-digital/typo3-frontend-api/commit/3a6e4a727576271c10c65dd0aaa444a8e685ab9b))

### [9.15.2](https://github.com/labor-digital/typo3-frontend-api/compare/v9.15.1...v9.15.2) (2020-05-14)


### Bug Fixes

* **ContentElement:** fix wrong event bus instantiation ([7cf08b7](https://github.com/labor-digital/typo3-frontend-api/commit/7cf08b77fe92a8e19bece0887c88d2cc9fffe20e))

### [9.15.1](https://github.com/labor-digital/typo3-frontend-api/compare/v9.15.0...v9.15.1) (2020-05-12)


### Bug Fixes

* **ContentElement:** pass http exceptions correctly to the error handler when thrown inside the content element handler ([6927b52](https://github.com/labor-digital/typo3-frontend-api/commit/6927b52ff509b1ecb7a6d035bdd311e87d274cf3))

## [9.15.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.14.0...v9.15.0) (2020-05-12)


### Features

* remove deprecated dependencies ([d647122](https://github.com/labor-digital/typo3-frontend-api/commit/d64712254230c5a751220ead1de04e340ae1d042))

## [9.14.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.13.0...v9.14.0) (2020-05-12)


### Features

* better implementation for special object transformation without registering them as resources ([10ed1e1](https://github.com/labor-digital/typo3-frontend-api/commit/10ed1e1ffaaa1997fbc68fa37d9cf993bcc9fb17))

## [9.13.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.12.0...v9.13.0) (2020-05-11)


### Features

* **FrontendSimulationMiddleware:** make site resolution more resilient ([4e38416](https://github.com/labor-digital/typo3-frontend-api/commit/4e38416978108d0a2a3ceadce853026b2d58921c))

## [9.12.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.11.0...v9.12.0) (2020-04-30)


### Features

* **Page:** add the siteUrl to the page data for the frontend ([670cbda](https://github.com/labor-digital/typo3-frontend-api/commit/670cbda580290183f2e25d9b2016a809052c4a78))

## [9.11.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.10.0...v9.11.0) (2020-04-19)


### Features

* mark all makeInstance methods deprecated and replace them with proper constructors ([87fe596](https://github.com/labor-digital/typo3-frontend-api/commit/87fe5960871932f54a32892aac770fa57e5dab91))
* **ApiRouter:** add redirect support for api requests ([b74fa81](https://github.com/labor-digital/typo3-frontend-api/commit/b74fa81e5c69f8913348a21769df3df8e8cf0fcc))
* **JsonApi:** implement official pid endpoint and hook it up to the page data ([415ffa8](https://github.com/labor-digital/typo3-frontend-api/commit/415ffa818d7dafa220fce904289c7fd11b52f3fe))

## [9.10.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.9.1...v9.10.0) (2020-04-17)


### Features

* **PageData:** add support for page data sliding -> inherit configuration from parent pages ([4944b39](https://github.com/labor-digital/typo3-frontend-api/commit/4944b39a0e9e68ae275040665f55b7754fa14e78))


### Bug Fixes

* **Imaging:** make lookup of default crop variant more reliable ([a18f4bd](https://github.com/labor-digital/typo3-frontend-api/commit/a18f4bdab42057de3851fa8588ccc5b7c42b70a8))

### [9.9.1](https://github.com/labor-digital/typo3-frontend-api/compare/v9.9.0...v9.9.1) (2020-04-15)


### Bug Fixes

* **Page:** make sure the correct page layout is returned ([ea1307c](https://github.com/labor-digital/typo3-frontend-api/commit/ea1307c6a3fc06ac6783eb63a8720de9a4016d1d))

## [9.9.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.8.0...v9.9.0) (2020-04-15)


### Features

* **Imaging:** prevent file system bloating using the file hash + make endpoint aware of crop changes ([33ad237](https://github.com/labor-digital/typo3-frontend-api/commit/33ad23771fd50ca900903ecbf3c6640fe00cbb27))


### Bug Fixes

* **VirtualColumn:** make sure the ref-index is correctly generated for virtual columns ([4507403](https://github.com/labor-digital/typo3-frontend-api/commit/450740309ecf23fe5f5a924e7bfdfef61cad73ef))

## [9.8.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.7.0...v9.8.0) (2020-04-13)


### Features

* **Page:** add "refreshCommon" query argument to refresh common elements while a page is rendered ([161b258](https://github.com/labor-digital/typo3-frontend-api/commit/161b258b74ff7f01b1212fafaae1edb1fc43fbcf))
* **Page:** allow filtering of the raw root line array before it is processed by the API ([b869887](https://github.com/labor-digital/typo3-frontend-api/commit/b8698875f96d3ab90d8244aaf4c825587ae52d9e))


### Bug Fixes

* **Page:** make sure the common elements are returned by default again ([f452b68](https://github.com/labor-digital/typo3-frontend-api/commit/f452b68c600e762bf26c6f097cdf16edb5f06935))

## [9.7.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.6.0...v9.7.0) (2020-04-11)


### Features

* **Imaging:** add x2 option to render retina images + use new resize image options trait ([6b00a60](https://github.com/labor-digital/typo3-frontend-api/commit/6b00a60ab65bce89c575dbfab2952b82aa09ef8d))

## [9.6.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.5.0...v9.6.0) (2020-04-07)


### Features

* add imaging feature ([33f477b](https://github.com/labor-digital/typo3-frontend-api/commit/33f477b6d5dcbc00336c64a76b2c186e577373f5))

## [9.5.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.4.0...v9.5.0) (2020-03-30)


### Features

* **PageData:** add more relevant fields to the page data model + add event to filter page info array before processing it ([1242254](https://github.com/labor-digital/typo3-frontend-api/commit/1242254a97d76a9ad353c19cc66b0836645d2127))
* **PageData:** extend support for meta tag generation for build in fields + allow filtering via event ([b3d6132](https://github.com/labor-digital/typo3-frontend-api/commit/b3d6132fae3f59ee5b2546d3c5c46887a0aa59f7))

## [9.4.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.3.0...v9.4.0) (2020-03-29)


### Features

* **Page:** add more reliable page layout lookup for the page entity ([ea2983a](https://github.com/labor-digital/typo3-frontend-api/commit/ea2983a22642afa8c6de0cd8903d063154707fe5))


### Bug Fixes

* **VirtualColumnEventHandler:** fix data handler event extraction ([402bcc4](https://github.com/labor-digital/typo3-frontend-api/commit/402bcc4cf6318e8f82a72923ecbe44128d75c6e4))

## [9.3.0](https://github.com/labor-digital/typo3-frontend-api/compare/v9.2.1...v9.3.0) (2020-03-27)


### Features

* **ContentElement:** always override content element previews ([6a942a7](https://github.com/labor-digital/typo3-frontend-api/commit/6a942a7a0dd91a80adbafecea2e9ac9d1ed12bd8))
* **ResourceTransformer:** implement a more reliable parsing solution for RTE link/html parsing ([4f49c52](https://github.com/labor-digital/typo3-frontend-api/commit/4f49c523de4a14802dfee3b205283790c741b74a))


### Bug Fixes

* **ContentElementConfigurator:** make ctype registration more straight forward ([3388ccb](https://github.com/labor-digital/typo3-frontend-api/commit/3388ccb42c5ddb827752feb1806139178dd1ee2c))
* fix action handler registration for content elements ([565b611](https://github.com/labor-digital/typo3-frontend-api/commit/565b611cb89485a3c395f946b78c50f41c39783a))
* **ContentElementConfigurator:** register data handler field constraints on the correct c-type when overwriting an existing element ([af6dc9f](https://github.com/labor-digital/typo3-frontend-api/commit/af6dc9f07d65b52d2ceeb8423f43e129379f51bf))

### [9.2.1](https://github.com/labor-digital/typo3-frontend-api/compare/v9.2.0...v9.2.1) (2020-03-26)


### Bug Fixes

* **ContentElementOption:** make sure the element config cache file exists before including it ([5b2920c](https://github.com/labor-digital/typo3-frontend-api/commit/5b2920c809a80878993bf1c40fecd1564755361d))

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
