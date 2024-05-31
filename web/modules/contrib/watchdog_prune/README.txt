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

The Watchdog Prune module allows the user to selectively delete watchdog entries
based on criteria, like age.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/watchdog_prune

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/watchdog_prune


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

The Views Watchdog lets the user build a View/report from watchdog entries (use
Views Data Export to export to CSV). THIS module ensures the watchdog table
won't lose records too soon.

 * Views Watchdog - https://www.drupal.org/project/views_watchdog


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the project.
    2. Navigate to Administration > Configuration > Development > Watchdog Prune
       settings to configure.
    3. In order for this module to work, Drupal's built in setting Database log
       messages to keep must be set to All. (/admin/config/system/cron). Visit:
       https://www.drupal.org/docs/7/setting-up-cron/overview for more
       information on seting up cron jobs.
    4. Select whether or not watchdog entries should be pruned based on age from
       the dropdown.
    5. Insert the values of the watchdog entries to be deleted. Use the
       following format: watchdog_entry_type|age (example: php|-1 MONTH or
       system|-1 MONTH). Enter one value per line.
    6. Save configuration.


MAINTAINERS
-----------

Current maintainers:
 * Vishwa Chikate - vishwa.chikate@gmail.com
 
Original Idea:
 * Richard Peacock - richard@peacocksoftware.com

This project is sponsored by:
 * Peacock Software, LLC - https://www.drupal.org/peacock-software-llc
 * InfoBeans Technologies Limited -
   https://www.drupal.org/infobeans-technologies-limited
 * Srijan Technologies - https://www.drupal.org/srijan-technologies
