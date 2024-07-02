# Upgrade Status

This module scans the code of installed contributed and custom projects on the
site, and reports any deprecated code that must be replaced before the next
major version. Available project updates are also suggested to keep your site
up to date as projects will resolve deprecation errors over time.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/upgrade_status).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/upgrade_status).

## Requirements

This module requires no modules outside of Drupal core.

## Installation

You must use Composer to install all the required third party dependencies,
for example `composer require drupal/upgrade_status` then install as you would
normally install a contributed Drupal module. For further information, see:
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

While the module takes an effort to categorize projects properly, installing
[Composer Deploy](https://www.drupal.org/project/composer_deploy) or
[Git Deploy](https://www.drupal.org/project/git_deploy) as appropriate to your
Drupal setup is suggested to identify custom vs. contributed projects more
accurately and gather version information leading to useful available update
information.

## Configuration

There are no configuration options. Go to Administration » Reports » Upgrade
status to use the module.

## Maintainers

- Gábor Hojtsy - [Gábor Hojtsy](https://www.drupal.org/u/g%C3%A1bor-hojtsy)
- Daniel Kudwien - [sun](https://www.drupal.org/u/sun)
- Angie Byron - [webchick](https://www.drupal.org/u/webchick)
- Jess - [xjm](https://www.drupal.org/u/xjm)
- Colan Schwartz - [colan](https://www.drupal.org/u/colan)
- Zoltán Herczog - [herczogzoltan](https://www.drupal.org/u/herczogzoltan)
