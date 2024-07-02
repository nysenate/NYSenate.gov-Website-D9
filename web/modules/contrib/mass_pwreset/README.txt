CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Mass Password Reset module allows users with "Administer users" permission
to reset all user accounts and notify all users.

 * For a full description of the module visit:
   https://www.drupal.org/project/mass_pwreset

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/mass_pwreset


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Mass Password Reset module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > People > Mass Password Reset for
       configurations.
    3. Select either all users (authenicated role) or select specific roles for
       the users you want to reset.
    4. Select to notify active and blocked user accounts using the Drupal
       password recovery email system.
    5. To reset the administrator account (uid 1) you must also select
       "Include admin user".
    6. Start the process by selecting the "Reset Passwords" button.

The user accounts will be updated in a batch session. The uid will be logged for
each user and the finished batch will be logged as well.


MAINTAINERS
-----------

 * Mark Shropshire (shrop) - https://www.drupal.org/u/shrop
 * Mike Barkas (mikebarkas) - https://www.drupal.org/u/mikebarkas
