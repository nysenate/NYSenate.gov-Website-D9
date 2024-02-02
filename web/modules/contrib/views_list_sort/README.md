CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

Views List Sort allows views to be sorted by a list field's allowed values.
This is useful if the allowed values are stored in a non-alphabetical order,
but you want to present your view results in the same order as your allowed
values are stored.


REQUIREMENTS
------------

dependencies:
  - drupal:views


INSTALLATION
------------

* Install as usual, see http://drupal.org/node/895232 for further information.


CONFIGURATION
-------------

1. Enable the module.
2. Add a "List (text)" sort field to your view.
3. In the sort field settings, set "Sort by allowed values" to "yes".

Drupal 8 port of https://www.drupal.org/project/views_list_sort.
