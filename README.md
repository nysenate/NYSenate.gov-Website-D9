# Composer template for Drupal projects
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

# Setup & Installation Steps

## 1. Setting up a [DDEV-Local](https://ddev.readthedocs.io/en/stable/) environment

### Install DDEV-Local on host machine
* Follow installation instructions at https://ddev.readthedocs.io/en/stable/

### Install composer on host machine
- On MacOS ```brew install composer```
- Otherwise, see instructions here https://getcomposer.org/

### Clone this project:
- `$ git clone https://bitbucket.org/mediacurrent/drupal-project.git`
- `$ cd drupal-project`

### Configure DDEV-Local
* Non-interactive configuration. Project names must be alphanumeric and/or hyphenated.
`$ ddev config --docroot=web --project-name="example" --project-type=drupal9 --webserver-type="nginx-fpm" --create-docroot`
* Interactive configuration alternative
`$ ddev config`

#### Using NFS for Better Performance
On both MacOS and Windows hosts, Docker performance can be sluggish on large projects with many directories. In those cases, it's recommended that you use DDEV's NFS volume feature to mitigate the performance losses. If you're on Linux, you shouldn't have to enable NFS due to its native support for Docker volumes.

For more information about DDEV's NFS feature, see:

- https://ddev.readthedocs.io/en/stable/users/performance/#using-nfs-to-mount-the-project-into-the-container
- https://github.com/drud/ddev/blob/master/scripts/macos_ddev_nfs_setup.sh#L41


### Start DDEV
- `$ ddev start`

### Initialize Project:
- `$ composer install`
- `$ ./scripts/hobson project:init example.ddev.site`
- `$ ddev restart`
*	This runs composer install.  As this is the first time being run, it is a composer update and calculates all dependencies.
* The domain is shown in the output of ddev start,  It is also available via ddev describe.
* This command ensures the config/config.yml is in place and has the domain set.
- For continued project development, proceed to the next section: "Rename & configure sample 'mis_profile' install profile".

## 1a. (Alternate) Setting up a local [Vagrant](http://vagrantup.com) environment

### Install composer on host machine
- On MacOS ```brew install composer```
* MacOS users may need to install the developer command line tools.
- Otherwise, see instructions here https://getcomposer.org/

### Clone this project and Initialize Project:
- `$ git clone https://bitbucket.org/mediacurrent/drupal-project.git`
- `$ cd drupal-project`
- `$ composer install`
- `$ ./scripts/hobson project:init example.mcdev 192.168.50.4`
- `$ ./scripts/hobson project:create-drush-alias`
* This runs composer install.  As this is the first time being run, it is a composer update and calculates all dependencies.
* This command ensures the config/config.yml is in place and has the domain and IP set. Edit config/config.yml to enable any additional features.
- Before the first time you run the build script, proceed to the next section: "Rename & configure sample 'mis_profile' install profile".

## 2. Configure the Site
After configuring either DDEV or Drupal VM, complete the remaining configuration steps.

### Configure Visual Regression test urls
- Edit "tests/visual-regression/backstop.js" environments to use the correct urls for any of the local, dev, staging, or prod urls known.

### Rename & configure sample 'mis_profile' install profile
- Change this to the name of your project name ( Copy contrib/mis_profile directory to custom/profilename)
- Find and replace all instances of 'mis_profile' with your project name
- Enable desired base profile features and modules (see mis_profile.install for more instructions).

### Run the build script.
- `$ ./scripts/build.sh`

This script automates the following steps:

* Runs composer install
* Ensures vagrant is available
* Starts vagrant if required
* Installs the project Drupal site

The initial pass of the build script downloads several dependencies and an intermittent internet connection will affect the initial build process.

### Compile the theme (required).
Note that the theme will not render correctly without running npm.

Follow the [rain_theme project README](https://bitbucket.org/mediacurrent/rain_theme/src/4.x/README.md) for build instructions.

### Troubleshooting
* Ensure Vagrant has provisioned without errors. Correct errors before proceeding. After vagrant provision is successful it maybe be helpful to vagrant halt && vagrant up

## 3. Drush Alias
* Use the project's [drush alias file](drush/example.mcdev.aliases.drushrc.php)
* Optionally copy into your user's drush directory at ~/.drush/ for global use or customization.

## 4. Logging In
* Use `drush @example.mcdev uli` to login to your local installation.

## 5. Adding the sync folder to be used with new installs
* The first time build.sh runs successfully you will be able to export configuration back to your project's sync folder.
* Add an empty folder named 'sync' at profile/profilename/config/sync.
* Add `
$settings['config_sync_directory'] = $app_root . '/profiles/profilename/config/sync';` to your local settings.php.
* Run `drush @example.mcdev cex` to export configuration to the sync folder.
* Re-run `$ ./scripts/build.sh` to test install with sync configuration.
* Once this is working as expected, add the sync folder to git and commit.

## 6. Development Settings
* The ./web/sites/example.mcdev/settings.local.php contains settings for customizing the development environment.  This disables Drupal's built in caching and additionally activates sites/development.services.yml for further customizing the development environment.

# Development Workflow

* [Use Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed) to add 3rd party dependencies and patches.
* Write custom modules, themes etc. to the ./web/ directory.
* Run `drush @example.mcdev cex` to export Drupal configuration to the profile/profilename/config/sync folder.
* Run `$ ./scripts/build.sh` before starting a new ticket. Run build.sh again to test work completed prior to submitting a pull request.

## 5. Demo Content
* TBD

The [drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold) plugin can download the scaffold files (like
index.php, update.php, …) to the web/ directory of your project. If you have not customized those files you could choose
to not check them into your version control system (e.g. git). If that is the case for your project it might be
convenient to automatically run the drupal-scaffold plugin after every install or update of your project. You can
achieve that by registering `@composer drupal:scaffold` as post-install and post-update command in your composer.json:

```json
"scripts": {
    "post-install-cmd": [
        "@composer drupal:scaffold",
        "..."
    ],
    "post-update-cmd": [
        "@composer drupal:scaffold",
        "..."
    ]
},
```
### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```
### How do I switch from packagist.drupal-composer.org to packages.drupal.org?

Follow the instructions in the [documentation on drupal.org](https://www.drupal.org/docs/develop/using-composer/using-packagesdrupalorg).

### How do I specify a PHP version ?

Currently Drupal 8 supports PHP 5.5.9 as minimum version (see [Drupal 8 PHP requirements](https://www.drupal.org/docs/8/system-requirements/drupal-8-php-requirements)), however it's possible that a `composer update` will upgrade some package that will then require PHP 7+.

To prevent this you can add this code to specify the PHP version you want to use in the `config` section of `composer.json`:
```json
"config": {
    "sort-packages": true,
    "platform": {"php": "5.5.9"}
},
```
## Additional Links
* [Project Drupal Theme Guide](https://bitbucket.org/mediacurrent/drupal-project.git/src/HEAD/web/themes/custom/project_theme/README.md?fileviewer=file-view-default)
* [Using Vagrant](https://bitbucket.org/mediacurrent/mis_vagrant/src/HEAD/README.md)
* This repository created from [Composer template for Drupal projects](https://github.com/drupal-composer/drupal-project/blob/8.x/README.md) which has some addition information on usage.
* [Using Composer](https://www.drupal.org/docs/develop/using-composer) with Drupal.
