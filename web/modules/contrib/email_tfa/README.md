CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

-- INTRODUCTION --

- Two-factor authentication by email, using user
- registered email to send a verification code to that 
- email every time the user try to login.

-- REQUIREMENTS --

* Make sure your site sends emails out.

-- INSTALLATION --
* Install as usual, see:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.

-- WARNING --
* After you enable the module you will need to activate it via module settings
 but keep in mind that all logged-in users will 
 be logged-out including the user just activate the module.
 
-- CONFIGURATION --
* Go to configuration » people » Email TFA Settings.
 Select Active to activate module and there will be 2 pathways for this module.

* Globally Enabled: When this option is selected Email TFA 
 is required for all users but you still have some
 exclude options for users one and rules of your choice.
* Users optionally can enable: Every user will have the option
 to enable this feature when editing profile.

-- Maintainers --
Current maintainers:
* Abdulaziz zaid (abdulaziz1zaid) - https://www.drupal.org/u/abdulaziz1zaid
* Essam AlQaie (3ssom) - https://www.drupal.org/u/3ssom
