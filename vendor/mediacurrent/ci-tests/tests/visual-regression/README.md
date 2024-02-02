Default configuration and README for visual regression testing

# Contents of This File

* [Overview of BackstopJS](#overview-of-backstopjs)
* [Using a JavaScript Config File](#using-a-javascript-config-file)
* [Quick Start with Docker](quick-start-with-docker)
* [Development Workflow](development-workflow)
* [BackstopJS Setup for Local Machines](#backstopjs-setup-for-local-machines)
* [BackstopJS Commands and Use](#backstopjs-commands-and-use)
* [Sites with Basic Auth](#sites-with-basic-auth)
* [Using BackstopJS for Feature Branches](#using-backstopjs-for-feature-branches)
* [Using BackstopJS for Deploys](#using-backstopjs-for-deploys)
* [Using BackstopJS to Compare Two Different Environments](#using-backstopjs-to-compare-two-different-environments)
* [Additional Notes](#additional-notes)
* [Credits](#credits)

# Overview of BackstopJS

BackstopJS is an npm package used for visual regression testing. It creates screenshot references based on CSS selectors, and runs subsequent tests against those screenshots to check for changes. More information and documentation can be found [here](https://www.npmjs.com/package/backstopjs), including how to re-run a reference for a single item, instead of the entire config file.

# Using a JavaScript Config File

By default, BackstopJS uses JSON config files. This setup means that you need a separate config file for different environments, which gets pretty tedious if you need to update URL paths. Instead, we've started using JavaScript config files, which allow us to create an array of relative URLs to use against any environment, based on arguments used in your BackstopJS commands. Many thanks to [Tom Phippen](http://fivemilemedia.co.uk/blog/backstopjs-javascript-configuration) for his blog post on Javascript config setup.

# Quick Start with Docker

* `cd tests/visual-regression`
* `npm install`
* `make local-reference`
* `make local-test`
* `make local-report`

# Development Workflow

* Run `make local-reference` before beginning new development
* Run `make local-test` to create report of development state
* Run `make local-report` to open report for review of changes

# BackstopJS Setup for Local Machines

This is for a fresh setup of BackstopJS on a project. See [BackstopJS Commands and Use](#backstopjs-commands-and-use) below for information on running visual regression tests when BackstopJS is already set up on a project.

* Make sure you have `npm` and `node` installed
* Create a feature branch for this setup, so you can merge in the changes once you're done
* Create a `/tests/backstop` directory in the project root
* Copy over the contents inside this `visual-regression` directory to the project's `tests/backstop` directory. That should include:
    * generic `.env` file
    * generic `backstop.js` file
    * generic `Makefile`
    * `package-lock.json`
    * `package.json`
    * generic `paths.js`
* Run `npm install` to get local packages, including `backstopJS`, `dotenv` (used for sites with basic auth) and `minimist` (used to implement our custom JS config instead of the default JSON config)
    * **NOTE:** With the current version of BackstopJS (as of June 2018), Puppeteer is the recommended engine to use for screenshots.
* Add `/tests/backstop/backstop_data` and `/tests/backstop/node_modules` to the `.gitignore` in the project root
* In the `backstop.js` file, update the generic `dev URL`, `staging URL`, and `prod URL` values.
* *If desired*, update the `delay` and `misMatchThreshold` values in the `scenarios.push` array
* *If desired*, update or add viewport settings in the `module.exports`
* In the `paths.js` file, add your relative URLs to the `pathConfig` array
* Create a README in `<project root>/tests/backstop`, and just add a link back to the [visual regression README](https://github.com/metaltoad/visual-regression/blob/master/README.md).
    * This is to make viz reg updates simpler; instead of having a visual regression README per project, there will just be a single source of truth README in the main Visual Regression repo.

# BackstopJS Commands and Use

This section is for using BackstopJS after it's already been set up on a project. If you need information about setting up a project with BackstopJS, please see the [BackstopJS Setup for Local Machines](#backstopjs-setup-for-local-machines) section above.

If BackstopJS is already set up on a project, but you haven't used it yet, you'll need to run through the following steps first:

* `npm install -g backstopjs` ( skip if using Docker)
* Navigate to the `/tests/backstop` folder and run `npm install`

NOTE: The project's `.gitignore` file includes `/tests/backstop/backstop_data`, so you won't see this when you visit the project for the first time. The `backstop_data` folder is created automatically when you run references and tests, and its location in the `backstop` directory is determined by the `saveDirectories` variable in the `backstop.js` configuration.

## Makefile

The Makefile allows us to vastly simplify the commands, as far as what you actually have to remember to type in. Make commands are only for comparing an environment against itself, and only for dev, staging, and prod environments.

If you're comparing two different environments, you'll need to check out the section below on [Using BackstopJS to Compare Two Different Environments](#using-backstopjs-to-compare-two-different-environments).

If you're using BackstopJS to check on regressions during local development, you'll want to check out the section on [Using BackstopJS for Feature Branches](#using-backstopjs-for-feature-branches).

Make commands look like this:
* `make prod-reference` or `make prod-test`

The Makefile defaults to using docker to run the tests.  To use the command installed in the PATH ( npm global install):

* `make DOCKER=false local-reference` or `make DOCKER=false local-test`

## Long-Form BackstopJS Commands

Although the Makefile replaces these commands when comparing the same environment, it's good to know what the Makefile is actually doing.

The full BackstopJS commands look something like this:
* References: `backstop reference --configPath=backstop.js --pathFile=paths --env=<environment> --refHost=<environment URL>`
* Tests: `backstop test --configPath=backstop.js --pathFile=paths --env=<environment> --testHost=<environment URL>`
* Reports: `backstop openReport --configPath=backstop.js --env=<environment>`
  * After the test completes, BackstopJS should automatically open the visual regression report in a new browser window. You would run the `openReport` command in order to manually open the report.

Let's break that down:
* `configPath`: The BackstopJS configuration file
* `pathFile`: The filename (without the file extension) that contains the array of relative URLs
* `env`: The environment you're testing: `local`, `dev`, `staging`, or `prod`
    * This value determines the screenshots' filenames, and the directory names where the screenshots are stored
    * Remember that the `env` argument needs to be the same for both reference and test commands
    * You can use something generic like `screenshots` if you're comparing two different environments
* `refHost` / `testHost`: The base URL of the website that you're testing

So if you're running references on the dev environment of a website, your command would be something like: `backstop reference --configPath=backstop.js --pathFile=paths --env=dev`. Note that there isn't a `refHost` argument here. That's because our config file sets the URL based on the `--env` value, IF you use `dev`, `staging`, or `prod`.

# Sites with Basic Auth

If the site you're using BackstopJS for has basic auth, you'll need to add a few things.
* Make sure the `package-lock.json` includes the [dotenv](https://www.npmjs.com/package/dotenv) package.
  * If the package is already listed in the file, it'll be installed when you run `npm install` in the `backstop` directory.
  * If the package hasn't been installed yet, but the `package-lock.json` file already exists, you'll need to run `npm install dotenv --save` to add it
  * If there isn't currently a `package-lock.json` file, it'll be created when you run `npm install dotenv`.

* After you have a `package-lock.json` file with the `dotenv` package included, copy over the [.env](https://github.com/metaltoad/visual-regression/blob/master/.env) file from the Visual Regression repo into the project's `backstop` directory.
* In the `.env` file, replace the placeholder values for `BASIC_AUTH` with the relevant values for the project site.
* In `backstop.js`, replace the relevant environment URLs.
  * Example: `'dev': 'http://dev-site.com'` would become `'dev': 'http://'+process.env.BASIC_AUTH+'\@dev-site.com'`
* In the project's `.gitignore` file, add the updated `.env` file so you don't commit basic auth to the repo.
* In the `backstop.js` file, uncomment the line `var dotenv = require('dotenv').config();`.

# Using BackstopJS for Feature Branches

1. Check out the dev branch, or whichever branch you want to use as source of truth
2. Seed with whichever database is source of truth
3. Make sure your local site is functional. It's terrible to spend the time running BackstopJS on a borked site.
4. Navigate to `<project root>/tests/backstop` and run the `reference` command.
5. When the reference screenshots are done, check out the feature branch and run any necessary updates, such as feature reverts, database updates, and CSS compiling
6. Make sure you're back in `<project root>/tests/backstop`, and run the `test` command.

# Using BackstopJS for Deploys

When deploying up the environments to prod, you'll want to re-run references and tests against each environment to ensure no visual regressions are introduced. Since you're not testing locally, it won't matter what branch you're on. Using the staging environment as an example, the general practice for this would be:

1. Navigate to the project root
2. Run the appropriate `make` command to get the pre-deploy reference screenshots.
3. Once the references have finished running, deploy the code and make any necessary updates, like indexing Solr or clearing caches
4. Again from the project root, run the appropriate `make` command for the post-deploy test screenshots
5. Check the report and make sure nothing's borked

OR

1. Navigate back to `<project root>/tests/backstop`
2. Run the BackstopJS `reference` command to get the pre-deploy screenshots.
3. Once the references have finished running, deploy the code and make any necessary changes, like indexing Solr or clearing caches
4. Run the BackstopJS `test` command
5. Check the report and make sure nothing's borked

# Using BackstopJS to Compare Two Different Environments

You can use BackstopJS with different environments for reference and test. This could be useful in a few cases, one of which is launching a new site. With this setup, you can run references against staging environment, and then run a test against prod on the initial prod deploy to make sure it looks like you expect.

When comparing different environments, make sure you use the same value for your `--env` argument. Remember that the `--env` value determines the directory naming for screenshots and reports, which is how BackstopJS compares the images and generates the report. I'd suggest using something generic like `screenshots` to avoid confusion.

Commands:
* Reference (staging): `backstop reference --configPath=backstop.js --pathFile=paths --env=screenshots --refHost=http://staging-site.com`
* Test (prod): `backstop test --configPath=backstop.js --pathFile=paths --env=screenshots --testHost=http://prod-site.com`

# Additional Notes

While running the BackstopJS test, you may receive an error in Terminal similar to:

```
Error: ENFILE: file table overflow, open '/Users/<user>/Sites/<project>/backstop_data/bitmaps_test/20160502-170325/49_18_footer_3_desktop.png'
    at Error (native)
```
The [solution](http://superuser.com/questions/827984/open-files-limit-does-not-work-as-before-in-osx-yosemite/828010#828010) for this is to: Open `/etc/sysctl.conf` in an IDE (or create `sysctl.conf` in your /etc folder, if it doesn't already exist) and insert the following settings:
```
kern.sysv.shmmax=1073741824
kern.sysv.shmmin=1
kern.sysv.shmmni=4096
kern.sysv.shmseg=32
kern.sysv.shmall=1179648
kern.maxfilesperproc=65536
kern.maxfiles=65536
```
# Credits

* Forked from Angela Riggs' https://github.com/angelariggs/visual-regression