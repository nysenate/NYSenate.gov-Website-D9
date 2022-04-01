CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Node Revision Delete module lets you to track and prune old revisions of
content types.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/node_revision_delete

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/node_revision_delete


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * Drush Help (https://www.drupal.org/project/drush_help):
   Improves the module help page showing information about the module's Drush
   commands.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.


CONFIGURATION
-------------

 * Configure the module in Administration » Configuration »
   Content authoring » Node Revision Delete:

   - You can set how many revisions you want to delete per cron run and
     how often should revisions be deleted when cron runs. You can save
     the configuration and optionally start a batch job to delete old revisions
     for tracked content types. For this you need the
     'Administer Node Revision Delete' permission.

 * Configure each content type in Administration » Structure » Content types »
   Content type name:

   - Under the Publishing options tab, mark "Limit the number of revisions for
     this content type" and set the maximum number of revisions to keep.

 * Drush commands

   - drush node-revision-delete

     Deletes old node revisions for a given content type.

   - drush nrd-delete-cron-run

     Configures how many revisions to delete per cron run.

   - drush nrd-last-execute

     Get the last time that the node revision delete was made.

   - drush nrd-set-time

     Configures the frequency with which to delete revisions when cron runs.

   - drush nrd-get-time

     Shows the frequency with which to delete revisions when cron runs.

   - drush nrd-when-to-delete-time

     Configures the time options for the inactivity time that the revision must
     have to be deleted.

   - drush nrd-minimum-age-to-delete-time

     Configures time options for the minimum age that the revision must be
     to be deleted.

   - drush nrd-delete-prior-revisions

     Delete all revisions prior to a revision.


FAQ
---

Q: How can I delete prior revisions?

A: When you are deleting a revision of a node, a new checkbox will appear in a
   fieldset saying: "Also delete X revisions prior to this one."; if you check
   it, all prior revisions will be deleted as well for the given node.
   If you are deleting the oldest revision, the checkbox will not appear as no
   prior revisions are available.


MAINTAINERS
-----------

Current maintainers:
 * Adrian Cid Almaguer (adriancid) - https://www.drupal.org/u/adriancid
 * Diosbel Mezquía (diosbelmezquia) - https://www.drupal.org/u/diosbelmezquia
 * Robert Ngo (Robert Ngo) - https://www.drupal.org/u/robert-ngo


This project has been sponsored by:

 * Ville de Montréal
 * Lullabot
 * Sapient
