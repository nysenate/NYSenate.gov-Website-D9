Charts
======

The Charts module provides a unified format to build any kind of chart with any
chart provider.

Each chart solution found on internet, such as Google Charts or Highcharts,
has a specific data scheme. Its very hard and even impossible to build a unique
chart data scheme that would be used in more that one chart provider. Or users
get bound to a solution forever. Or they have to rewrite all exported data
again.

That's why Charts is so great. It uses a standard data scheme to describe charts
data, and through filters, it automatically converts to each solution. You can
change to another solution at anytime.

The Chart schema is very similar to Drupal's Form API schema.

Chart Providers
---------------

Out of the Box, you will be able to use 4 chart solutions. Each of them has
particular advantages and disadvantages.

* C3: This library is a D3-based reusable chart library makes it easy to
  generate D3-based charts by wrapping the code required to construct the
  entire chart. You don't need to write D3 code any more.

* Chart.js: This is a simple yet flexible JavaScript charting for designers
  & developers.

* Google Charts: This library does not require any external downloads. It
  generates interactive charts using SVG and VML.

* Highcharts: This library is one of the premier solutions for generating
  charts. Although it is very powerful and aesthetically pleasing with smooth
  animations, it requires a commercial license. It's free for non-commercial
  use. See http://www.highcharts.com

Installing Libraries
---------------------

Using Composer:

1: Ensure that you have the `composer/installers` package installed.
2: Ensure you have an installer-paths for the drupal-library type. Such as in
   the composer.json of
   https://github.com/drupal-composer/drupal-project/blob/8.x/composer.json
3: In each sub-module, there is a README.txt file that you can use to copy the
   info inside the composer.json of your project.
4: After updating your project's composer.json file, run the 'composer require'
   command specified in each sub-module's README.txt file. For example, if you
   are using Google Charts, step three would mean adding:

        {
            "type": "package",
            "package": {
                "name": "google/charts",
                "version": "45",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "google_charts"
                },
                "dist": {
                    "url": "https://www.gstatic.com/charts/loader.js",
                    "type": "file"
                },
                "require": {
                    "composer/installers": "~1.0"
                }
            }
        }

   to your project's composer.json, and then running:

        composer require --prefer-dist google/charts:45


There are numerous tutorials on Drupal.org and elsewhere on the web if you are
looking for more information about how to use Composer with Drupal 8.


Using Composer and wikimedia/composer-merge-plugin:

1: Ensure that you have the 'wikimedia/composer-merge-plugin` package installed.
2: Ensure that you have the `oomphinc/composer-installers-extender` package
   installed.
3: Add an "installer-types" section in the "extra" of your project composer.json
   file, make sure you have "bower-asset" and "npm-asset" listed.
   For example:
     "installer-types": [
         "bower-asset",
         "npm-asset"
     ],
4: In the "installer-paths" section in the "extra" of your project composer.json
   file, ensure you have an the types drupal-library, bower-asset, and npm-asset.
   For example:
     "web/libraries/{$name}": [
         "type:drupal-library",
         "type:bower-asset",
         "type:npm-asset"
     ],
5: Add a "merge-plugin" section in the "extra" of your project composer.json
   file, so that the composer.json file of the sub-modules you want is included.
   For example:
     "merge-plugin": {
         "include": [
             "web/modules/contrib/charts/modules/charts_highcharts/composer.json"
         ]
     },
6: Run the 'composer require' specified in the sub-module's README.txt file


Creating Charts in the UI
-------------------------

This module provides a configuration page at admin/config/content/charts. You
may set site-wide defaults on this page (for example set the default color
scheme).

In order to actually create a chart through the UI, you'll need to use Views
module.

- Create a new view:
  Visit admin/structure/views/add and select the display format of "Chart" for
  your new page or block.

- Add a label field:
  Under the "Fields" section, add a field you would like to be used as labels
  along one axis of the chart (or slices of the pie).

- Add data fields:
  Now a second field that will be used to determine the data values. If you
  are visualizing an Event content type, this field might be
  field_number_attendees. The label you give this field will be used in the
  chart's legend to represent this series. Do this again for
  each different quantity you would like to chart. Note that some charts
  (e.g. Pie) only support a single data column.

- Configure the chart display:
  Click on the "Settings" link in the Format section to configure the chart.
  Select your chart type. Some options may not be available to all chart types
  and will be adjusted based on the type selected.

- Save your view.

Tip: You may find it easier to start with a "Table" display and convert it to a
chart display after setting up the data. It can be easier to visualize what
the result of the chart will be if it's been laid out in a table first.

Creating Multiple Series and Combo Charts in the UI
---------------------------------------------------

A major difference between the Drupal 7 and Drupal 8 versions of this module is
that the Drupal 8 module uses a Chart Attachment plugin for creating a separate
chart series that can be attached to a parent display.

Using Charts with custom Views Fields
-------------------------------------

If you are using custom views fields (extended from
Drupal\views\Plugin\views\field\FieldPluginBase) to generate numbers or other
data, you will need to set the getValue() function in your custom field to
return your data. Data returned in render() is not used when building the chart.

You may look in src/Util/Util.php to see how the field data is extracted and
used by the Charts module.

Support
-------

For bug reports and feature requests please use the Drupal.org issue tracker:
http://drupal.org/project/issues/charts.

We welcome your support in improving code documentation, tests, and providing
example use-cases not addressed by the existing module.

If you are interested in creating your own sub-module for a library not
currently supported (for example, Flot), please contact @andileco
