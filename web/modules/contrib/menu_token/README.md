MENU TOKEN
----------

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

Menu Token module provides tokens, that could be used in title
or in path of menu items (links). For example, if you create
a menu item with path: "user/[current-user:uid]", the url will be
changed "on fly" to: "user/1" (assuming you are user 1).

Tokens are provided by `Token` module. Menu Token allows to use
both global tokens and entity ones: node, user, term, etc. Entity tokens
have several methods of substitution: from context, random and user defined.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/menu_token

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/menu_token


REQUIREMENTS
------------

This module requires the following module:

 * Token (https://www.drupal.org/project/token)
 

RECOMMENDED MODULES
-------------------

 * Menu HTML (https://www.drupal.org/project/menu_html):
   Use Menu HTML module and select "Allow html" in your menu item.

 * Superfish (https://www.drupal.org/project/superfish):
   Superfish integrates jQuery Superfish plugin with your Drupal menus. Use Superfish 1.9-beta5 or greater. 

 * Extended Path Aliases (https://www.drupal.org/project/path_alias_xt):   
   Use Extended path aliases to automatically translate paths like 'user/1/mycontent' to 'users/admin/mycontent'. Just need to create a 'root' path alias: 'users/admin'.

 * Menu item visibility (https://www.drupal.org/project/menu_item_visibility):       
   Menu item visibility exposes configurable and extendable visibility settings for menu links. You'll need to set module weights so that Devel node access > Menu item visibility > Menu token.
   
 * Tokenize Request Parameters (http://drupal.org/project/token_request_params):
   You can use Tokenize Request Parameters module along with the Menu Token to configure this sort of functionality without the need to code a new module. Tokenize Request Parameter allows you to define what URL parameters to convert into tokens. It makes the tokens available to any token module that consumes tokens (Token Filter, Menu Token, Rules, etc.) 
   
   
INSTALLATION
------------

Install as usual, see
 https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8 for further
information.

Via drush: $ drush dl menu_token && drush en -y menu_token


CONFIGURATION
-------------

You can goto the page `admin/config/menu_token`, and check the boxes to
limit which entities are available in Menu Token's context settings select
boxes.

And when you need to use menu token in your menu item,
you should check the box `Use tokens in title and in path`
just below the menu item path.


KNOWN ISSUES
------------

 1) There are modules that use same hook as Menu Token,
    they need to be executed in proper order.
    For example: Menu per Role (https://www.drupal.org/project/menu_per_role)
	works well with a weight of 15.
 2) Some weird behavior happens when not using absolute paths, please help
    us solve it once and for all! #2099623: When do you use [site-url] token?
    (https://www.drupal.org/node/2099623)
	
	
MAINTAINERS
-----------

Current maintainers:
 * Fernando Paredes García (develCuy) - https://www.drupal.org/user/125473
 * Peter Draucbaher (peter.draucbaher)- https://www.drupal.org/user/2409142 
