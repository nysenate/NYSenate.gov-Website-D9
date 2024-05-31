CONTENTS OF THIS FILE
--------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

The Semantic Views module provides a Views plugins for UI management of output markup. Semantic Views allows you to alter the default HTML output by the Views module. 

* For a full description of the module visit https://www.drupal.org/docs/8/modules/semantic-views

* To submit bug reports and feature suggestions, or to track changes visit https://www.drupal.org/project/issues/semanticviews


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

* Install the Semantic Views module as you would normally install a contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------

1. Navigate to Administration > Extend and enable the Semantic Views module.
2. Navigate to Administration > Structure > Views and next to the view to edit, select the "Edit” button.
3. In the "Format" fieldset, select the link next to "Format:" (by default it is set to Unformatted list). Select "Semantic Views Style" and Apply the changes.
4. Select the "Settings" link to view the available options.
5. The “Grouping field Nr.1” dropdown allows users to group the records by a chosen field. If an option is selected, the user may use rendered output to group the rows or remove tags from output.
6. The "Grouping Title" fieldset allows users to change Elements and Class Attributions when using groups. The view will insert the Grouping's Title Field.
7. The "List" fieldset provides choices for List types and Class attributions. Note: if the output is a HTML list, the row element should also be set to "li".
8. The "Row" fieldset provides choices for the Row's Elements and Class attributions. The rows can also be defined by "First/Last every nth" and by "Striping class attributes".
9. Apply any changes and save the view.


MAINTAINERS
-----------

* Scyther - https://www.drupal.org/u/scyther
