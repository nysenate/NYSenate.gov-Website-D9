CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

The Site Verification module verifies ownership of a site for use with search engines. It assists with the site/domain ownership authentication/verification for search engines. There are two types of verification methods supported: adding meta tags, or uploading a specific file. If you are provided with a file to upload, this module makes it easy because you can upload the file in the module's interface and the proper filename and file contents will be saved and used in the authentication process.

* For a full description of the module, visit this page:
https://www.drupal.org/project/site_verify

* To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/issues/site_verify


REQUIREMENTS
------------

This module requires no other modules outside of Drupal core.


INSTALLATION
------------

Install the Site Verification module as you would normally install a contributed Drupal module. Visit https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for further information.


CONFIGURATION
-------------

To Add a verification to a site:
1. Navigate to Administration > Configuration > Search > Verifications.
2. To add a verification select "Add verification".
3. Select the search engine to which you would like to send verification.
4. "Verification META tag" is the full meta tag provided for verification, it is only visible in the source code of the front page.
5. There is an option to provide a verification file. Either upload a file directly or provide its name and contents to "Verification file" and "Verification file contents" respectively. Site contents could be left empty to use default content.
6. Save.

To Edit an existing verification:
1. Navigate to Administration > Configuration > Search > Verifications.
2. Select Edit next to the search engine to be edited.
3. Make appropriate edits.
4. Save.

To obtain the verification files, use one or more of the following services.

GOOGLE
Create a Google Webmaster Tools Account:
* https://www.google.com/webmasters/tools/home
* https://support.google.com/webmasters/answer/35179

Bing
Create a Bing Webmaster Tools Account:
* http://www.bing.com/toolbox/webmaster
* http://www.bing.com/webmaster/help/how-to-verify-ownership-of-your-site-afcfefc6

Yandex
Create Yandex webmaster account:
* https://webmaster.yandex.com
* https://yandex.com/support/webmaster/service/rights.html

Yahoo
Yahoo verification is considered obsolete.
* Visit: https://www.drupal.org/node/1412198


MAINTAINERS
-----------

* Jim.M - https://www.drupal.org/u/jimm
* Dave Reid - https://www.drupal.org/u/dave-reid
