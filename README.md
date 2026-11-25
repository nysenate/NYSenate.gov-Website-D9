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

## Adding or updating Composer patches

Patch definitions live in `composer.json`; Composer Patches generates `patches.lock.json`
to lock their contents and checksums. After adding or updating a patch definition or local patch file, run:

```sh
ddev composer patches-relock
ddev composer patches-repatch
ddev composer update --lock
```

**Warning:** `patches-repatch` replaces installed patched dependencies, discarding any local
changes inside them. Review the patch and lockfile diffs, test the affected functionality,
and include the related patch definitions/files and both lockfiles in the same change.
Do not regenerate checksums just to bypass an unexpected patch integrity failure.

Normal `composer install` uses the existing patch lockfile without refreshing it. After
pulling someone else's patch changes, use `ddev composer patches-repatch` to apply them
to an existing installation; a fresh installation only needs `ddev composer install`.

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
If a branch is created off of `main` with the naming convention `feature/JIRA-1234`, upon creating a pull request,
the [PR workflow](.github/workflows/pantheon-deploy-multidev.yml) uses Pantheon's Push to Pantheon Action to create or update
`pr-<number>`. Integrated Composer and Frontend Asset Builds are configured in [pantheon.yml](pantheon.yml);
Pantheon installs PHP dependencies and builds `nysenate_theme` and `nys` with Node 22.
New Multidevs clone only the database from `PANTHEON_MULTIDEV_CLONE_ENV` (`--no-files`).
The Action handles creation and Git deployment with `skip_build_tools: true` and `clone_files: false`;
this remains a GitHub Actions workflow, not the Pantheon GitHub Application integration.
Existing Multidevs must be in Git connection mode; review/preserve any SFTP code changes before switching modes.
Stage File Proxy continues to supply public files on demand; only `private_settings.php` is copied from that same
source over SFTP after the push. Subsequent pushes reuse the database and refresh that one settings file.
Before cache and accessibility tests run, CI checks the remote Git revision and requires Terminus to confirm a
successful workflow for that commit (up to 20 minutes). An unchanged rerun can reuse its prior successful build.

This is a WIP platform-build trial. Inspect Pantheon's build logs and deployed revision before accepting the result;
an available URL or green deployment status alone is not proof of a successful current build.
The manual Dev artifact workflow has not yet been migrated: do not merge/promote the native build configuration or
run that workflow against it until the Dev deployment path is reconciled. The current `main` configuration is unchanged.
