CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage


INTRODUCTION
------------

Login History adds a new table which stores information about individual user
logins, including a timestamp,IP address, user agent information, and whether
or not the login was via a reset password link.

Based on this data there are a few pieces of functionality provided by this
module:

 * A global report of all user logins.
 * Per-user login reports.
 * A block that can show the user information about their last login and link
   to their per-user login report if they have access to it.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/login_history

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/login_history


REQUIREMENTS
------------

 * This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

The module has no Configuration settings.


USAGE
------

 * To Check history of login users visit
   /admin/reports/login-history
