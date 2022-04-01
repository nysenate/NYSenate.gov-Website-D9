# SVG Image module

## CONTENTS OF THIS FILE

 - [Introduction](#introduction)
 - [Features](#features)
 - [Requirements](#requirements)
 - [Installation](#installation)
 - [Configuration](#configuration)
 - [Maintainers](#maintainers)

## INTRODUCTION

This module changes default image field widget and formatter to allow use SVG image with the standard Image field.

Using SVG Image module you will not need to use another field to use SVG image instead of the already created Image field.

## FEATURES

Beyond the  main functionality module allows site builder to:
* Add a custom width and height to the image in the formatter
* Choose how to render image - using `<svg>` tag or `<img>`

## REQUIREMENTS
This module requires:
* Drupal core module "Image"
* PHP > 5.6
* SimpleXML library (Drupal core also requires it)

Module was tested on Drupal versions:
* 8.3.x
* 8.4.x
* 8.5.x

## INSTALLATION
Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/extending-drupal/installing-drupal-modules for further information.

## CONFIGURATION
All configuration is available via Image field settings.
You can set up:
- Display image as SVG (Using `<svg>` HTML tag). `<img>` tag will be used otherwise
- Display SVG image with predefined *width* and *height* (It will not be applied to the other image types)

MAINTAINERS
-----------
* [Yaroslav Lushnikov (zvse)](https://www.drupal.org/user/2870933)

Drupal8 version of the module was developed with [DrupalJedi](https://www.drupal.org/drupaljedi) support.
