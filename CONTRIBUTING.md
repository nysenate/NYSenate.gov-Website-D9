# How to contribute

Thanks for your interest in contributing to https://www.nysenate.gov/. We aim to
provide transparency and accountability in New York State Senate. We welcome
your help in making this site better.

## Submitting changes

First, please open a Github issue to discuss what you would like to change.
[NYS Github Issues](https://github.com/nysenate/NYSenate.gov-Website-D9/issues)

If you have a bug fix or new feature, you can fork the repository, make your
changes, and submit a pull request.

Please follow our coding conventions (below) and make sure all of your commits
are atomic (one feature per commit).

Always write a clear log message for your commits. Including the JIRA ticket
number or Github issue number is helpful.

`$ git commit -m "Issue-### A brief summary of the commit"`

## Coding conventions

We follow the [Drupal coding standards](https://www.drupal.org/docs/develop/standards).

### Code Sniffing with grumphp

Please ensure that grumphp is configured and running on your local environment.
`composer install` after cloning the repository.

Ensure that `.git/hooks/pre-commit` includes the following:

```
#!/bin/sh

#
# Run the hook command.
# Note: this will be replaced by the real command during copy.
#

# Fetch the GIT diff and format it as command input:
DIFF=$(git -c diff.mnemonicprefix=false -c diff.noprefix=false --no-pager diff -r -p -m -M --full-index --no-color --staged | cat)

# Grumphp env vars

export GRUMPHP_GIT_WORKING_DIR="$(git rev-parse --show-toplevel)"

# Run GrumPHP
(cd "./" && printf "%s\n" "${DIFF}" | exec 'vendor/bin/grumphp' 'git:pre-commit' '--skip-success-output')
```

You can manually run grumphp by executing the following command:

`vendor/bin/grumphp git:pre-commit`

### Code Sniffing Rules

- PHP Code Sniffer (phpcs) is used to enforce Drupal coding standards. See
`phpcs.xml.dist` for what is being enforced.
- PHPStan is used to enforce PHP static analysis. See `phpstan.neon.dist` for
  what is being enforced. At this point, we are using level 0, which is the
  lowest level of strictness. It will catch deprecated functions and methods.

### Code Sniffing with VSCode and PHPStorm
You can easily configure your IDE to use the phpcs and phpstan rules. This will
allow you to see errors and warnings in your IDE as you code and prevent
frustration when you try to commit your code.

### VSCode
1. Install https://marketplace.visualstudio.com/items?itemName=shevaua.phpcs
2. Configure the extension to use the `phpcs.xml.dist` file in the root of the
   repository.

### PHPStorm (JetBrains)
1. See https://www.jetbrains.com/help/phpstorm/using-php-code-sniffer.html#installing-configuring-code-sniffer
2. Configure the plugins to use the `phpcs.xml.dist` and `phpstan.neon.dist`
   files in the root of the repository.

## Accepting changes
NYSenate.gov reserves the right to accept or reject any changes. We will review
your changes and provide feedback. We may ask you to make changes before we
accept your pull request.
