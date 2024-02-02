# ci-tests
Scripts helpful for testing a Drupal site on a Continuous-Integration server, or locally.

This package provides example tests:

     behat/behat-run.sh      Behavior Driven Development
     code-fixer.sh           PHP Code Beautifier and Fixer
     code-sniffer.sh         PHP CodeSniffer
     cypress                 Cypress End-to-End Testing
     pa11y/pa11y-review.sh   Pa11y Accessibility
     phpunit.sh              PHPUnit
     security-review.sh      Drupal 7 Security Review module

## Installation

```
composer require mediacurrent/ci-tests
```

It may be necessary to define the package in the repositories section of composer.json:

```
"repositories": [
    {
        "type": "vcs",
        "url": "git@bitbucket.org:mediacurrent/ci-tests.git"
    }
],
```

The following script is used during a new project setup:
```
./vendor/mediacurrent/ci-tests/scripts/tests-init.sh
```
> **_NOTE:_** This copies the `./vendor/mediacurrent/ci-tests/tests` directory to `./tests`.  This script will not overwrite any existing files.

## Credits

- Thanks to https://drupal.org/project/doobie for example Behat configuration.
- Thanks to [Jonathan Daggerhart](https://www.drupal.org/u/daggerhart) for Cypress examples and supporting setup code. See: https://github.com/daggerhartlab/cypress-drupal.
