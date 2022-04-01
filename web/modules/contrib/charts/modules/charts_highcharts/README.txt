Installation using Composer (recommended)
========================================

If you use Composer to manage dependencies, edit "/composer.json" as follows.

  1. Run "composer require --prefer-dist composer/installers" to ensure that you
     have the "composer/installers" package installed. This package facilitates
     the installation of packages into directories other than "/vendor" (e.g.
     "/libraries") using Composer.

  2. Add the following to the "installer-paths" section of "composer.json":

     "libraries/{$name}": ["type:drupal-library"],

  3. Add the following to the "repositories" section of "composer.json":

        {
            "type": "package",
            "package": {
                "name": "highcharts/highcharts",
                "version": "8.1.0",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "highcharts"
                },
                "dist": {
                    "url": "https://code.highcharts.com/8.1.0/highcharts.js",
                    "type": "file"
                },
                "require": {
                    "composer/installers": "~1.0"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "highcharts/more",
                "version": "8.1.0",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "highcharts_more"
                },
                "dist": {
                    "url": "https://code.highcharts.com/8.1.0/highcharts-more.js",
                    "type": "file"
                },
                "require": {
                    "composer/installers": "~1.0"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "highcharts/exporting",
                "version": "8.1.0",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "highcharts_exporting"
                },
                "dist": {
                    "url": "https://code.highcharts.com/8.1.0/modules/exporting.js",
                    "type": "file"
                },
                "require": {
                    "composer/installers": "~1.0"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "highcharts/export-data",
                "version": "8.1.0",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "highcharts_export-data"
                },
                "dist": {
                    "url": "https://code.highcharts.com/8.1.0/modules/export-data.js",
                    "type": "file"
                },
                "require": {
                    "composer/installers": "~1.0"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "highcharts/accessibility",
                "version": "8.1.0",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "highcharts_accessibility"
                },
                "dist": {
                    "url": "https://code.highcharts.com/8.1.0/modules/accessibility.js",
                    "type": "file"
                },
                "require": {
                    "composer/installers": "~1.0"
                }
            }
        },
        {
            "type": "package",
                "package": {
                "name": "highcharts/3d",
                "version": "8.1.0",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "highcharts_3d"
                },
                "dist": {
                    "url": "https://code.highcharts.com/8.1.0/highcharts-3d.js",
                    "type": "file"
                },
                "require": {
                    "composer/installers": "~1.0"
                }
            }
        }

4. Run "composer require --prefer-dist highcharts/highcharts:8.1.0 highcharts/more:8.1.0 highcharts/exporting:8.1.0 highcharts/export-data:8.1.0 highcharts/accessibility:8.1.0 highcharts/3d:8.1.0"
- you should find that new directories have been created under "/libraries"
