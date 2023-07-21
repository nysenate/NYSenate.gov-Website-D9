# Cypress Testing

## Cypress Usage
Update `./tests/cypress/cypress.config.js` for your environment.

Change to the Cypress test directory:
```
cd ./tests/cypress
```

Set up the Node environment and install Cypress:
```
nvm use
nvm install
npm install
```

## Cypress E2E Testing

Runs Cypress tests from the CLI without the GUI:
```
npx cypress run
```

Opens Cypress in the interactive GUI:
```
npx cypress open
```
> **_NOTE:_** Run `npx cypress help` for more information.

## Docker Quickstart

`make local-run`

## Makefile

The Makefile allows us to vastly simplify the commands, as far as what you actually have to remember to type in.
Specify the environment urls in the Makefile
Default environments are local, ci, and dev.

Make command examples:

* `make local-run` - Run via Docker to test local DDEV site using default tags ( "@smoke --@bug --@wip")
* `make local-run tags="@p1"` - Run via Docker test local DDEV site using "@p1" tags
* `make local-open` - Open the Cypress interactive GUI
* `make dev-run` - Run via Docker to test the dev site using default tags

The Makefile defaults to using docker to run the tests.  To use Cypress installed via npm ( npx cypress):

* `make DOCKER=false local-run` or `make DOCKER=false local-run`
* `make local-open` Runs this way by configuration already

## Tags

`npm cypress --env grepTags="@smoke"` or `make local-run tags="@smoke"`

Reference all tags used in TAGS.md.
