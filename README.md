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

### Build & Start the local environment

- `$ cd nys_nysenate_gov.git`
- `$ ddev start`
- `$ ./scripts/build.sh`

This script automates the following steps:

* Runs composer install
* Installs the project Drupal site

The initial pass of the build script downloads several dependencies and an intermittent internet connection will affect the initial build process.

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
* Run `$ ./scripts/build.sh` before starting a new ticket. Run build.sh again to test work completed prior to submitting a pull request.

## Demo Content
* TBD

## Additional Links
* [Project Drupal Theme Guide](https://bitbucket.org/mediacurrent/nys_nysenate_gov.git/src/HEAD/web/themes/custom/project_theme/README.md?fileviewer=file-view-default)
* [DDEV-Local Documentation](https://ddev.readthedocs.io/en/stable/)
* This repository created from [Composer template for Drupal projects](https://github.com/drupal-composer/drupal-project/blob/9.x/README.md) which has some addition information on usage.
* [Using Composer](https://www.drupal.org/docs/develop/using-composer) with Drupal.
