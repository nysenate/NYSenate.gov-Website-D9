CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Hacked! module scans the currently installed Drupal contributed modules and 
themes, re-downloads them and determines if they have been changed.  Changes are
marked clearly and if the Diff module is installed then Hacked! will allow you 
to see the exact lines that have changed.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/hacked

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/hacked


REQUIREMENTS
------------

This module does not require any additional modules outside of Drupal core.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Reports > Hacked. The "List projects" tab 
    will provide a list of modules which have changes. Each list item provides 
    a "View details of changes" link which gives a more detailed list of what 
    exact changes occurred.
    The "Settings" tab allows two options: "Ignore line endings" which is 
    helpful if the project has been edited on a platform different from the 
    original author's (E.g. if a file has been opened and saved on Windows) and
    "Include line endings" when hashing files differences in line endings will 
    be included.
    2. To run a report which includes disabled modules, navigate to 
    Administration > Reports > Updates > Settings and click the "Check for 
    updates of disabled and uninstalled modules and themes" checkbox. Save 
    configuration.
    3. The process of fetching and comparing with clean projects can be a 
    time-consuming one, so give it some time to load on the first run.


MAINTAINERS
-----------

Current maintainers:
 * Andrei Ivnitskii - https://www.drupal.org/u/ivnish
