CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Help and assistance
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

Provides AJAX comments to Drupal sites (commenting like a social networking
sites: Facebook, vk.com etc).

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/ajax_comments

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/ajax_comments


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the AJAX Comments module as you would normally install a contributed
   Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


HELP AND ASSISTANCE
-------------------

Help is available in the issue queue. If you are asking for help, please
provide the following information with your request:

 * The comment settings for your node type
 * The ajax comments settings
 * The browsers you are testing with and version
 * Any relevant information from the console or a similar Javascript debugger
 * Screenshots or screencast videos showing your errors are helpful
 * If you are using the default textarea or any 3rd party Javascript editors
   like CKEditor, etc.
 * Any additional details that the module authors can use to reproduce this
   with a default installation of Drupal.


CONFIGURATION
-------------

  1. Navigate to `Administration > Extend` and enable the module.
  2. Navigate to `Administration > Configuration > Content Authoring >
     AJAX comments` for configuration.


TROUBLESHOOTING
---------------

 * IMPORTANT: If you have the "Comment Notify" module installed, please also
   install http://drupal.org/project/queue_mail to prevent server errors
   during comment submitting.
 * The module may conflict with Devel. It has been causing lags when a
   comment is submitting.
 * Try testing with Aggregate JavaScript files disabled and see if it makes a
   difference. (/admin/config/development/performance)
 * If you are having issues, first try the module with a clean Drupal
   install with the default theme. As this is Javascript, it relies upon
   certain assumptions in the theme files.


MAINTAINERS
-----------

 * Alexander Shvets (neochief) - https://www.drupal.org/u/neochief
 * Dan Muzyka (danmuzyka) - https://www.drupal.org/u/danmuzyka
 * Andrew Belousoff (formatC'vt) - https://www.drupal.org/u/formatcvt
 * Volkan Flörchinger (muschpusch) - https://www.drupal.org/u/muschpusch
 * acouch - https://www.drupal.org/u/acouch
 * Anton Kuzmenko (qzmenko) - https://www.drupal.org/u/qzmenko
 * Ide Braakman (idebr) - https://www.drupal.org/u/idebr
