# TYPO3 - Frontend API

This package provides your TYPO3 installation with extended API capabilities. Create a REST-ful api was never that easy in TYPO3. It provides you with an
extended router based on the [PHP league's route package](https://route.thephpleague.com/), it works in tandem with the already existing PSR-7 middleware stack
but provides you with the option to register your own middleware specially for API requests. The bundle also has build in support for converting ext-base domain
objects into [json-api](https://jsonapi.org/) resources using [fractal](https://fractal.thephpleague.com/).

The main focus while designing the extension was to provide all capabilities to create a Single Page App using TYPO3. For that reason the package comes with
endpoints for menus, translations, page content rendering and automatic generation of content element data representations.

Another key-aspect of this package is the easy handling of content elements. The package provides a powerfull extension to extbase controllers that allow you to
render data based elements instead of fluid styled content.

## Requirements

- TYPO3 v10
- [TYPO3 - Better API](https://github.com/labor-digital/typo3-better-api)
- Installation using Composer

## Known issues

- This extension is NOT tested with workspaces, and will probably not work correctly.

## Installation

Install this package using composer:

```
composer require labor-digital/typo3-frontend-api
```

After that, you can activate the extension in the Extensions-Manager of your TYPO3 installation

## Example extension

To take a look a how to use the extension, you can take a look at the [example extension](https://github.com/labor-digital/typo3-frontend-api-example).

## Documentation

After a complete rewrite of the extension for TYPO3 v10 the old documentation has to be rewritten as well. I'm currently working on that.

[comment]: <> (The documentation can be found [here]&#40;https://typo3-frontend-api.labor.tools&#41;.)

[comment]: <> (## Building the documentation)

[comment]: <> (The documentation is powered by [vuepress]&#40;https://vuepress.vuejs.org/&#41;, you can quite simply spin up a dev server like so:)

[comment]: <> (- Clone the repository)

[comment]: <> (- Navigate to ```docs```)

[comment]: <> (- Install the dependencies with ```npm install```)

[comment]: <> (- Run the dev server with ```npm run dev```)

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning
which of our package(s) you are using.

Our address is: LABOR.digital - Fischtorplatz 21 - 55116 Mainz, Germany

We publish all received postcards on our [company website](https://labor.digital). 
