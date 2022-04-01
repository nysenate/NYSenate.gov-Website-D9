CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
---------------------

A Module that can finally delete files properly!

What this module can do:

1. View of all managed files with an option to force delete them via VBO custom actions.
2. Manually deleting managed files by FID (and an option to force the delete if you really want to).
3. Deleting unused files from the default files directory that are not in the file managed table. AKA deleting all the unmanaged files.
4. Deleting unused files from the whole install that are no longer attached to nodes & the file usage table. AKA deleting all the orphaned files.
5. Delete files via drush by fid(s)

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/fancy_file_delete/

* To submit bug reports and feature suggestions, or track changes:
  https://www.drupal.org/project/issues/


REQUIREMENTS
------------

* Drupal 8/9
This module requires the following modules:

 * Views Bulk Operations VBO (https://www.drupal.org/project/views_bulk_operations)

You will also need a core patch so you can delete the files of other users as outlined in this issue...
* #3169116: Manual delete works while selecting from the list doesn't (https://www.drupal.org/project/fancy_file_delete/issues/3169116)
* The patch in this issue seems to work well (https://www.drupal.org/project/drupal/issues/2949017#comment-13420408)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

Drush Command

You can provide a single fid or a list of fids separated by commas.

drush fancy:file-delete FID
drush fancy-file-delete FID
drush ffd FID

Failure to add the fid to the drush command results in an error as a FYI.


MAINTAINERS
-----------

Current maintainers:
 * John Ouellet (labboy0276) - https://www.drupal.org/u/labboy0276
 * Daniel Pickering (ikit-claw) - https://www.drupal.org/u/ikit-claw
 
