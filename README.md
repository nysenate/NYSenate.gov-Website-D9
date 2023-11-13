# The New York State Senate - nysenate.gov
## Package features
### Asset packagist support
The Asset packagist package acts as a bridge between Composer and the popular NPM and Bower repositories, which catalog
thousands of useful front-end and JavaScript packages. This allows developers to easily pull in packages like DropZoneJS
and many others without requiring local Node.js to be installed.

Read more: https://lightning.acquia.com/blog/round-your-front-end-javascript-libraries-composer

### Guardr core security
Guardr is a Drupal distribution with a combination of modules and settings to enhance a Drupal application's security
and availability to meet enterprise security requirements. This project leverages Guardr's package management and
module configuration.

### Sub-profile support
A Drupal core patch has been included to add support for "Sub-profiles".

See: https://www.drupal.org/node/1356276

### Rain base profile features
The [Mediacurrent Rain base install profile](https://bitbucket.org/mediacurrent/mis_rain/) includes many of the most
common packages pre-configured for rapid site development and optional content features.

## Setting up a [DDEV-Local](https://ddev.readthedocs.io/en/stable/) environment

### Clone this repository into the directory of your choice:
- `$ git clone git@bitbucket.org:mediacurrent/nys_nysenate_gov.git`

### Install composer on host machine
If you haven't already, install composer:

- On MacOS ```brew install composer```
- Otherwise, see instructions here https://getcomposer.org/

### Set up Pantheon provider locally
If you haven't already, configure your local DDEV environment with a machine token from Pantheon. This will allow you to pull the database locally with a simple command.

See the [DDEV Pantheon provider documentation](https://ddev.readthedocs.io/en/latest/users/providers/pantheon/#pantheon-quickstart) for instructions.

### Build & start the local environment

- Switch to the repository path: `$ cd nys_nysenate_gov`
- Switch to the “develop” branch: `$ git checkout develop`
- Install composer dependencies: `$ composer install`
- Connect ddev to Pantheon: `$ ddev auth ssh`
- Duplicate the local settings file: `cp web/sites/example.settings.local.php web/sites/default/settings.local.php`
  - This makes the file available for making changes locally, should they be needed.
- Start the environment: `$ ddev start`
- Download a database backup from the production/live environment:
  `$ ddev pull pantheon --skip-files`
- Confirm that the site is loaded properly: `$ ddev drush st`
  - If this does not indicate a working site, proceed to the troubleshooting section.

Confirm you can browse to [https://nysenate.ddev.site](https://nysenate.ddev.site).

### Troubleshooting
* Ensure ddev has started without errors. Correct errors before proceeding.

## Initial Configuration

## Logging In
* Use `ddev drush uli` to login to your local installation.

## Development Settings
* The settings.local.php file contains settings for customizing the development environment.  This disables Drupal's built in caching and additionally activates sites/development.services.yml for further customizing the development environment.

# Development Workflow

* [Use Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed) to add 3rd party dependencies and patches.
* Write custom modules, themes etc. to the ./web/ directory.
* Run `ddev drush cex` to export Drupal configuration to the profile/profilename/config/sync folder.
* Make sure to run `ddev composer install` and then `ddev drush deploy` after pulling the latest from the `develop` branch. This will install the latest PHP dependencies and update/import the latest config into your database.

## Additional Links
* [Project Drupal Theme Guide](https://bitbucket.org/mediacurrent/nys_nysenate_gov.git/src/HEAD/web/themes/custom/project_theme/README.md?fileviewer=file-view-default)
* [DDEV-Local Documentation](https://ddev.readthedocs.io/en/stable/)
* This repository created from [Composer template for Drupal projects](https://github.com/drupal-composer/drupal-project/blob/9.x/README.md) which has some addition information on usage.
* [Using Composer](https://www.drupal.org/docs/develop/using-composer) with Drupal.

## Re-index Search API
* Due to the amount of content, there are some tips on how to get the site to re-index without timing out:
* If you have changes to search API configuration run `drush search-api-reset-tracker`
* Check `admin/config/search/search-api/index/core_search` to make sure that the tracking info has been reset if
not, click the button that says "track info" - you may need to do this a couple times to run all the way through.
* Run `drush sapi-i` to re-index and watch for any errors.
* If the re-index times out you can pick it back up where it left off by running `drush sapi-i` again.

## Github Actions
This should be documented.
