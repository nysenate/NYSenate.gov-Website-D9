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

The Node Revision Delete module lets you track and prune old revisions of
content types.

When new revisions are added for content, the content is added to a queue to
check whether revisions for the content are allowed to be deleted. The queue
calls Node Revision Delete Plugins for all revisions.

For each revision, a plugin can return TRUE if the revision is allowed to be
deleted, return FALSE if a revision can not be deleted, and return NULL if the
plugin has no opinion for the revision.

 * When one of the plugins returns FALSE, the revisions is always kept.
 * When no plugins return FALSE and at least one plugin returns TRUE, the
   revision is deleted.
 * When none of the plugins return TRUE, the revision is kept.

For a full description of the module, visit the project page:
https://www.drupal.org/project/node_revision_delete

To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/issues/search/node_revision_delete


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * Drush Help (https://www.drupal.org/project/drush_help):
   Improves the module help page showing information about the module's Drush
   commands.

 * Queue UI (https://www.drupal.org/project/queue_ui):
   A user interfaces for viewing and managing Drupal queues created via the
   Queue API.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for
further information.


CONFIGURATION
-------------

**Default configuration**

Configure the module in
*Administration » Configuration » Content authoring » Node Revision Delete*

You can configure how many revisions you want to keep per content type and
configure how long revision should be kept. When saving the configuration you
can optionally start a batch job to queue all content to delete revisions that
are allowed to be deleted. For this you need the
'Administer Node Revision Delete' permission.

**Content type configuration**

Configure each content type in
*Administration » Structure » Content types » Content type name*

Under the Publishing options tab, configure how many revisions you want to
keep for the content type and how long revision should be kept.

**Drush commands**

 * drush *node-revision-delete:queue*. **Adds all content to a queue to delete revisions for the content.**
 * drush *nrd:delete-prior-revisions*. **Deletes all revisions prior to a revision**


Node Revision Delete Plugins
---------------
Plugins of type `NodeRevisionDelete` can be used to configure whether a
revision should be allowed to be deleted.

Node Revision Delete plugins must be placed in:

`[module name]/src/Plugin/NodeRevisionDelete/Display`.

Display plugins must at least implement the NodeRevisionDeleteInterface.
After creating a plugin, clear the cache to make Drupal recognize it.

NodeRevisionDelete annotation should at least contain:
```
 * @NodeRevisionDelete(
 *   id = "plugin_id",
 *   label = @Translation("Plugin name"),
 * )
```


MAINTAINERS
-----------

Current maintainers:
 * Adrian Cid Almaguer (adriancid) - https://www.drupal.org/u/adriancid
 * Diosbel Mezquía (diosbelmezquia) - https://www.drupal.org/u/diosbelmezquia
 * Robert Ngo (Robert Ngo) - https://www.drupal.org/u/robert-ngo
 * Sean Blommaert (seanB) - https://www.drupal.org/u/seanB
 * Pravin Gaikwad (Rajeshreeputra) - https://www.drupal.org/u/rajeshreeputra

This project has been sponsored by:

 * Drupiter
 * Ville de Montréal
 * Lullabot
 * Publicis Sapient
 * Erasmus University Rotterdam
