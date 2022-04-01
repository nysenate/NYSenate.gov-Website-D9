CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Views Data Export module provides the following formats for views:

 * CSV (via csv_serialization module)
 * Microsoft XLS (via the xls_serialization module)
 * Microsoft DOC (planned)
 * Basic TXT (planned)

Drupal 8.x already provides XML and JSON formats for Views via the Rest module.

 * For a full description of the module visit:
   https://www.drupal.org/project/views_data_export

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/views_data_export


REQUIREMENTS
------------

This module requires the following outside of Drupal core.

 * CSV Serialization - https://www.drupal.org/project/csv_serialization


INSTALLATION
------------

 * Install the Views Data Export module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Views and create a View Page
       that will display the information that you want to filter and export
       (You will go to the path of this page to generate the export once done).
       The View can be whatever display/format type that you need as it will be
       the interface that the user will filter by and then export data from.
    3. Add the fields that you want to display and include in the export, then
       add the exposed filters that you want to filter by in order to create
       custom exports (user role, status, pretty much any field you need).
    4. Add a new display and select Data export.
    5. From the Format field set, select the Data export settings. Select from:
       csv, json, or xml formats. Request formats that will be allowed in
       responses. If none are selected all formats will be allowed. Apply.
    6. From the Pager field set, set to 'display all items' for the data export.
       Otherwise the results will be limited to the number per page in the pager
       settings.
    7. From the Path Settings field set, change the "Attach to" settings to
       "Page".
       to attach the data export icon to the selected displays.
    8. Ensure that the 'path' for the data export is a file, with an extension
       matching the format. ie: /export/foo.csv. Otherwise, the file will be
       downloaded without a file association.
    9. Navigate to the path of the View Page to generate the report.


MAINTAINERS
-----------

 * Jonathan Hedstrom (jhedstrom) - https://www.drupal.org/u/jhedstrom
 * Steven Jones - https://www.drupal.org/u/steven-jones
 * James Silver (jamsilver) - https://www.drupal.org/u/jamsilver
 * Nathan Page (amoebanath) - https://www.drupal.org/u/amoebanath
 * James Williams (james.williams) - https://www.drupal.org/u/jameswilliams-0
 * Adam Bergstein (nerdstein) - https://www.drupal.org/u/nerdstein

For a full list of contributors visit:

 * https://www.drupal.org/node/980666/committers

Initial port to Drupal 8:

 * Workday, Inc. - https://www.drupal.org/workday-inc
