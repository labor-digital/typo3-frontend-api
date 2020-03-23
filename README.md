# TYPO3 - Frontend API
This package provides your TYPO3 installation with extended API capabilities. Create a REST-ful api was never that easy in TYPO3.
It provides you with an extended router based on the [PHP league's route package](https://route.thephpleague.com/), it works in tandem with the already existing PSR-7 middleware stack but provides
you with the option to register your own middleware specially for API requests. The bundle also has build in support for converting ext-base domain objects into [json-api](https://jsonapi.org/) resources using [fractal](https://fractal.thephpleague.com/).

The main focus while designing the extension was to provide all capabilities to create a Single Page App using TYPO3. For that reason the package comes
with endpoints for menus, translations, page content rendering and automatic generation of content element data representations.

Another key-aspect of this package is the easy handling of content elements. You can either use ext-base plugins/content elements,
but you can also use powerful template-less content elements that utilize the Better API syntax for TCA definition and registration.


## Requirements

- TYPO3 v9
- TYPO3 - Better API
- Installation using Composer

## Installation
Install this package using composer:

```
composer require labor-digital/typo3-frontend-api
```

## Documentation
The documentation can be found [here](https://typo3-frontend-api.labor.tools).

## Building the documentation
The documentation is powered by [vuepress](https://vuepress.vuejs.org/), you can quite simply spin up a dev server like so:

- Clone the repository
- Navigate to ```docs```
- Install the dependencies with ```npm install```
- Run the dev server with ```npm run dev```

## Postcardware
You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: LABOR.digital - Fischtorplatz 21 - 55116 Mainz, Germany

We publish all received postcards on our [company website](https://labor.digital). 
