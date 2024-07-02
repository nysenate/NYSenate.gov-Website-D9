# Session-limit module 

The session-limit module is designed to allow an administrator to limit
the number of simultaneous sessions a user may have.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/session_limit).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/session_limit).


## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
- Maintainers


## Requirements

This module requires the following modules:

- [Token](http://drupal.org/project/token)
- [String Overrides](http://drupal.org/project/stringoverrides)


## Recommended modules

- [Autologout](https://www.drupal.org/project/autologout): For limiting the length of time a user's session can last.
- [Password Policy](https://www.drupal.org/project/password_policy): For enforcing password length, complexity and renewal.
- [Ejector seat](https://www.drupal.org/project/ejectorseat): For periodically checking if a user has been logged out and 
  then reloading the page they are on so they know they need to login before proceeding.
- [Warden](https://www.drupal.org/project/warden): For an dashboard overview of the security status of a large estate of Drupal websites.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-modules).


## Configuration

- Go to Administration > Extend and enable the module.
- Go to _/admin/config/people/session-limit_.
- Triggers are available to assign any of the three system actions to either the
  collision or disconnect events. That includes displaying a message to the user,
  sending an email, or redirecting to a different URL.
- Rules events are available for collision or disconnect events.
- The precedence of defined session limits are:
  1. The user's session limit if set, otherwise,
  2. The highest session limit for a user as set on their roles, if all are set to default then
  3. The system default session_limit.


## Maintainers

- David N - [deekayen](https://www.drupal.org/u/deekayen)
- John Ennew -[johnennew](https://www.drupal.org/u/johnennew)
- Suzy Masri - [suzymasri](https://www.drupal.org/u/suzymasri)
- Vladimir Roudakov - [VladimirAus](https://www.drupal.org/u/vladimiraus)
- Nicolas Borda - [ipwa](https://www.drupal.org/u/ipwa)
- Matthew Lambert - [xiwar](https://www.drupal.org/u/xiwar)
