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
                "name": "chartjs/chartjs",
                "version": "v2.7.2",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "chartjs"
                },
                "dist": {
                    "url": "https://www.chartjs.org/dist/2.7.2/Chart.bundle.js",
                    "type": "file"
                }
            }
        }

4. Run "composer require --prefer-dist chartjs/chartjs:2.7.2"
- you should find that new directories have been created under "/libraries"


Please note that there is a Chart.js issue with mixed charts that
start with a line instead of a bar, for example:

https://github.com/chartjs/Chart.js/issues/5701
