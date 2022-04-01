
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Usage
 * Credits


INTRODUCTION 8.2.x version
------------
This module allows for the integration of fontIconPicker with the Font Awesome
module. fontIconPicker is a jQuery library intended to allow for easy search
and selection of icons. This module provides a widget to utilize this libary
alongside the Font Awesome module in lieu of the default "Search and select"
widget provided with Font Awesome.

INSTALLATION
------------

1. Using Drush (https://github.com/drush-ops/drush#readme)

    $ drush en fontawesome_iconpicker_widget

    Upon enabling, this will also attempt to download and install the library
    in `/libraries/fontawesome`. If, for whatever reason, this process
    fails, you can re-run the library install manually by first clearing Drush
    caches:

    $ drush cc drush

    and then using another drush command:-

    (Drush 8)
    $ drush fa-download-iconpicker
    (Drush 9)
    $ drush fa:download-iconpicker

2. Manually

    a. Install the "fontIconPicker" library following one of these 2 options:
       - run "drush fa-iconpicker-download" (recommended, it will download the
         correct package and extract it at the right place for you.)
       - manual install: Download & extract "fontIconPicker"
         (https://github.com/fontIconPicker/fontIconPicker/releases)
         and place in the "/libraries" directory at
         "/libraries/fonticonpicker--fonticonpicker".
         The JS file should be located at
         /libraries/fonticonpicker--fonticonpicker/dist/js/
         Direct link for downloading latest version (current is v3.1.1) is:
         https://github.com/fontIconPicker/fontIconPicker/releases/tag/v3.1.1
    b. Enable the module at Administer >> Site building >> Modules.

3. Composer

    a. Install the "fontIconPicker" using composer:
       - The easiest way to install the library with composer is if your project
         has "npm-asset" (see: https://asset-packagist.org/) repository
         configured, you can require "npm-asset/fonticonpicker--fonticonpicker".
         You can use oomphinc/composer-installers-extender to ensure the library
         is installed in libraries/.
    b. Enable the module with Drush or Administer >> Site building >> Modules.

USAGE
_____
Simply select "Font Awesome Iconpicker Widget" as your widget of choice for any
Font Awesome Icon field under "Manage form display".

TROUBLESHOOTING
---------------
If the Font Awesome Iconpicker module is installed but the fontIconPicker
selector is not showing, try the following:
1. Flush the Drupal cache
2. Check the status report for issues on the library being loaded.

CREDITS
-------
* Scott Sawyer https://drupal.org/u/scottsawyer
* Daniel Moberly https://drupal.org/u/danielmoberly
