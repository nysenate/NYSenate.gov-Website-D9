CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Views Send module allows mass mailing using Views. You can either queue the
messages in a spool table and deliver on cron, or you can select to send the
messages directly using the Batch API in stead. You can control how many messages
will be sent per cron run.

 * For a full description of the module visit:
   https://www.drupal.org/project/views_send

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/views_send


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

* Mime Mail - https://www.drupal.org/project/mimemail
  When the Mime Mail module is enabled, the user can choose to send rich HTML
  messages and/or use attachments.
* Tokens - https://www.drupal.org/project/token
  When the Tokens module is enabled, the user can insert context tokens into the
  subject or body of the message. Note that row-based tokens are available even
  if Tokens module is disabled.
* Rules - https://www.drupal.org/project/rules
  When the Rules module is enabled, the user can define actions for when emails
  are sent and/or placed in the spool.

Similar modules:

 * Simplenews - https://drupal.org/project/simplenews
   Some pieces of code were inspired by Simplenews module.


INSTALLATION
------------

 * Install the Views Send module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

To modify the module's default general settings navigate to
Administration > Configuration > System > Views Send

To actually use the module you have to create a view:

 1. Create a view with a page (or block) display
    and add at least one column containing e-mail addresses.
 2. [Optional] Expose filters to let the user easily build list of
    recipients using UI.
 3. Add the "Global: Send e-mail" field to your view. This field provides the
    checkboxes that allow the user to select multiple rows.
 4. [Optional] Add a field that contains recipient names, which can be used to
    generate display names in the "To" field when sending e-mails.
 4. Save the view, load the page (or a page with the block), use any exposed
    filters to build the list, select all or some rows and choose "Send e-mail".
 5. Fill the message form to configure the e-mail. Use tokens to personalize
    the e-mail.
 6. Preview and send the message.


MAINTAINERS
-----------

 * Module maintainer
   Hans Fredrik Nordhaug (hansfn) - https://drupal.org/user/40521
 * Module author of original Drupal 6 version
   Claudiu Cristea (claudiu.cristea) - https://drupal.org/user/56348
 * The Drupal 6 version of this module was sponsored by Grafit SRL -
   http://www.grafitsolutions.ro


HOW CAN YOU GET INVOLVED?

 * Help translate this module at Drupal Localize Server
   http://localize.drupal.org/translate/projects/views_send
