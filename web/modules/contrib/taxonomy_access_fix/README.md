
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

*Taxonomy Access Fix* module extends access handling of Drupal Core's Taxonomy module. It

* adds a per-vocabulary "View terms in VOCABULARY" permission to view published terms.
* adds a per-vocabulary "Reorder terms in VOCABULARY" permission.
* removes vocabularies the user doesn't have permission to either create, delete, edit or reorder terms in from the vocabulary overview page.

For more information about the module, visit the project page:
https://www.drupal.org/project/taxonomy_access_fix

To submit bug reports related to access checks or other security vulnerabilities:
https://security.drupal.org/node/add/project-issue/taxonomy_access_fix

To submit other bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/issues/taxonomy_access_fix


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Taxonomy Access Fix module as you would normally install a contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------
Change permissions as needed on the *Manage* -> *People* -> *Permissions* page.

A module can't add permissions on behalf of another module, so the extra permissions are listed under "Taxonomy Access Fix" and not under "Taxonomy".

The per-vocabulary "View terms in Vocabulary label" permission will allow users to view published terms. To view unpublished terms, users will need the permisison to "Administer vocabularies and terms" provided by Drupal Core.

To access the vocabulary overview page for a vocabulary, users must have permission to either

* create, edit, delete or reorder terms in that vocabulary in addition to the Taxonomy module's permission to "Access the taxonomy vocabulary overview page".
* to "Administer vocabularies and terms".


MAINTAINERS
-----------

* Oleksandr Dekhteruk (pifagor) - https://www.drupal.org/u/pifagor
* rudiedirkx - https://www.drupal.org/u/rudiedirkx

Supporting organizations:

* GOLEMS GABB - https://www.drupal.org/golems-gabb

