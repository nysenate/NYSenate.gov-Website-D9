CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Node Revision Generate module lets you to create node revisions. This module
is for test purpose.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/node_revision_delete

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/node_revision_delete


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * Devel Generate (https://www.drupal.org/project/devel):
   Generate dummy nodes.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.


CONFIGURATION
-------------

 * Generating revisions:

   - Go to Administration » Configuration » Content authoring » Node Revision
     Delete » Generate revisions and select the content type for which you are
     going to generate revisions, then select the number of revision to generate
     for each node and the age between each revision and click on the Generate
     revisions button. The first revision for each node will be generated
     starting from the created date of the last revision and the last one will
     not have a date in the future.


MAINTAINERS
-----------

Current maintainers:
 * Adrian Cid Almaguer (adriancid) - https://www.drupal.org/u/adriancid
 * Diosbel Mezquía (diosbelmezquia) - https://www.drupal.org/u/diosbelmezquia
 * Robert Ngo (Robert Ngo) - https://www.drupal.org/u/robert-ngo
