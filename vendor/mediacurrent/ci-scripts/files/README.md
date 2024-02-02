# Composer template for Drupal projects

## Documentation
See full documentation for Ignite CMS at https://mediacurrent.gitbook.io/ignite-cms/

## Setting up a [DDEV-Local](https://ddev.readthedocs.io/en/stable/) environment

### Clone this repository into the directory of your choice:
- `$ git clone {{ git_remote_url }}`

### Install composer on host machine
If you haven't already, install composer:

- On MacOS ```brew install composer```
- Otherwise, see instructions here https://getcomposer.org/

### Build & Start the local environment

- `$ cd {{ bitbucket_project }}`
- `$ ddev start`
- `$ ./scripts/build.sh`

This script automates the following steps:

* Runs composer install
* Installs the project Drupal site

The initial pass of the build script downloads several dependencies and an intermittent internet connection will affect the initial build process.

## Development Settings
* The settings.local.php file contains settings for customizing the development environment.  This disables Drupal's built in caching and additionally activates sites/development.services.yml for further customizing the development environment.

##### Drush commands you should know
* Use `ddev drush uli` to login to your local installation.
* `ddev drush cr` to flush the drupal cache
* `ddev drush cex` to export config changes
* `ddev drush cim` to import config changes

# Development Workflow

* [Use Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed) to add 3rd party dependencies and patches.
* Write custom modules, themes etc. to the ./web/ directory.
* Run `ddev drush cex` to export Drupal configuration to the config/sync folder.
* Run `$ ./scripts/build.sh` before starting a new ticket. Run build.sh again to test work completed prior to submitting a pull request.

#### Additional information:
- **Install DDEV-Local** on host machine, instructions at https://ddev.readthedocs.io/en/stable/
- **Install composer** on MacOS ```brew install composer```
- Otherwise, see instructions at https://getcomposer.org/

## Tests

### Run coding standards tests.

*NOTE* Tests will not run until modules are in the "web/modules/custom" directory.

- phpcs - `./tests/code-sniffer.sh ./web`
- phpcbf - `./tests/code-fixer.sh ./web`

### Drupal-check custom development for 10 readiness.

*NOTE* Checks will not run until modules are in the "web/modules/custom" directory.

- `./vendor/mediacurrent/ci-tests/tests/drupal-check.sh web`

### Run BDD tests.

- `ddev . tests/behat/behat-run.sh https://example.ddev.site`

### Run phpunit tests.

- unit tests - `composer robo test:phpunit-tests`
- kernel and functional tests - `ddev composer robo test:phpunit-tests -- --filter="/Kernel|Functional/"`

### Run VRT.

* Documentation in tests/visual-regression/README.md
* Start at "Quick Start with Docker"

### Run a11y tests.

*NOTE* Requires [pa11y](https://github.com/pa11y/pa11y#command-line-interface)

- `./tests/pa11y/pa11y-review.sh https://example.ddev.site`

### OWASP Zap Baseline Scan.

- `docker run --net=ddev_default -v $(pwd):/zap/wrk/:rw -t owasp/zap2docker-weekly zap-baseline.py -d -c owasp-zap.conf -p owasp-zap-progress.json -t https://ddev-<projectname>-web`

### GrumPHP

* [GrumPHP](https://github.com/phpro/grumphp) will run some tests on code to be committed. The file grumphp.yml is used to configure.
	* Coding Standards
	* Deny committing a list of debug keywords
	* json and yaml linting
	* Composer lock file validation
	* Enlightn Security Checker

- - -

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:

```
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```

### How do I specify a PHP version ?

This project supports PHP 8.1 as minimum version (see Environment requirements of Drupal 9), however it's possible that a composer update will upgrade some package that will then require PHP 8.1+.

To prevent this you can add this code to specify the PHP version you want to use in the config section of composer.json:

```
"config": {
    "sort-packages": true,
    "platform": {
        "php": "8.1"
    }
},
```

## Additional Links
* [Project Drupal Theme Guide](https://bitbucket.org/mediacurrent/{{ bitbucket_project }}/src/HEAD/web/themes/custom/project_theme/README.md?fileviewer=file-view-default)
* [DDEV-Local Documentation](https://ddev.readthedocs.io/en/stable/)
* This repository created from [Composer template for Drupal projects](https://github.com/drupal-composer/drupal-project/blob/9.x/README.md) which has some addition information on usage.
* [Using Composer](https://www.drupal.org/docs/develop/using-composer) with Drupal.
