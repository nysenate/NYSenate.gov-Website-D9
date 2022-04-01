# Thunder Admin Theme

An administration theme for the Thunder drupal distribution that extends and
modifies the styles of the core theme seven for authoring UX and an integrated
look and feel of the contributed modules used in the distribution.

## Basic structure

The Thunder Admin theme relies on Seven as a base theme, while overriding and extending it using libraries and
templates. For an overview on what has been done, refer to thunder_admin.info.yml and thunder_admin.libraries.yml where
overrides are listed and libraries are defined.

The theme facilitates SASS and introduces css-sniper, a node-sass importer plugin, which adds the ability to import css
directly from the original assets in core into component css via a build process. The build process is described below,
also see package.json for a list of tasks.

### Things that need refactoring:
* Asset folder structure needs to be improved to follow a clear concept.
* image and icon assets need to be consolidated and reworked according to a unified visual language and color scheme.
* Due to some quirks in the libraries-override, it is not possible to override single assets in libraries preserving
  the order of inclusion. All assets have to be overridden / imported, see #2642122 ([IMPORT_ONLY]).
* Some visual styles need to be refactored to be aligned with Thunder Admin's design in more areas than the current
  focus which is content authoring.

### Initializing Git Large File Storage (LFS)
Git LFS is used for storing of images for regression testing. In order to provide new images in a pull request, LFS has
to be installed on the system and initialized.

Installation instructions for Git LFS are provided at [git-lfs.github.com](https://git-lfs.github.com/).
After that initialization of LFS has to be done once: ``git lfs install``


### Set LFS filter for local development repository

After repository is cloned, it's preferred to setup LFS filter for screenshots folder. It can be done once with following line:
```echo "screenshots/reference/** filter=lfs diff=lfs merge=lfs -text" > .gitattributes```

After that following line should be executed to get all existing screenshots from repository:
```git lfs pull```

Every following `git` pull/push should work properly with LFS integration, as long `.gitattributes` is in local repository.

## Development workflow

Theme should be installed in correct Drupal environment (expected to be in: `[docroot]/themes/contrib/thunder_admin`).
Build script will try to resolve paths to `core` themes with globally available `drush` command. If that is not possible then it will try default fallback path.
If that does not work, then correct fallback path to `core` themes should be changed in `css-sniper.conf.js` property `fallbackThemesPath`, so that build script can find required `core` files.

This project uses Node.js LTS version, you can use [nvm](https://github.com/nvm-sh/nvm#installation-and-update).
Run `nvm install 12` to switch to install and use node 12 version.
Then run `npm prune` and `npm install`.

build scripts and watch scripts are run with npm, for development run
`npm run dev`.

or only watch changes in sass files
`npm run watch:styles`.

#### The build tasks that will be executed are:
* live-reloading dev server with browser-sync
  ([link_css](http://drupal.org/project/link_css) Drupal module required)
* SCSS processing and autoprefixing (folder: sass to css)
* image minification (folder: images-originals to images)
* svg sprite creation (folder: images/icons to images/icon-sprite.svg)
* JS linting (folder: js)

#### Visual Regression Tests
Travis will check the theme for changes with a visual regression test.
If you changed some styling, please provide new reference images.

You can use the continous integration infrastructure to update the visual regression reference images by adding [UPDATE_SCREENSHOTS] to your commit message

For creating screenshots locally you should install [GraphicsMagick](http://www.graphicsmagick.org/INSTALL-unix.html)
(on mac simply use `brew install graphicsmagick`) otherwise travis tests may fail.

Install a fresh thunder:

- `composer create-project burdamagazinorg/thunder-project:3.x ../fresh-thunder --stability dev --no-interaction --no-install`
- `cd ../fresh-thunder`
- `composer require thunder/thunder_testing_demo:4.x thunder/thunder_stylequide`
- replace installed thunder_admin theme with the one including your changes by copying or making a symbolic link
- configure database settings
- `drush si thunder --account-pass=admin install_configure_form.enable_update_status_module=NULL -y`
- if no images are visible: `drush cr -l <yourdomain:port>`

Then you can run selenium in docker:

- for Chrome testing start `docker run -d -P -p 6000:5900 -p 4444:4444 --shm-size 2g --add-host=theme.test:host-gateway selenium/standalone-chrome:3.141.59-20210713`
- for Firefox testing start `docker run -d -P -p 6000:5900 -p 4444:4444 --shm-size 2g --add-host=theme.test:host-gateway selenium/standalone-firefox:3.141.59-20200719`

To debug a browser you can use following commands:

- for Chrome testing start `docker run -d -P -p 6000:5900 -p 4444:4444 --shm-size 2g --add-host=theme.test:host-gateway selenium/standalone-chrome-debug:3.141.59-20210713`
- for Firefox testing start `docker run -d -P -p 6000:5900 -p 4444:4444 --shm-size 2g --add-host=theme.test:host-gateway selenium/standalone-firefox-debug:3.141.59-20200719`

and connect with you vnc client (on mac you can use finder: go to -> connect to server [⌘K]). The password is: `secret`

Before starting, set the correct URL in `sharpeye.conf.js`.
To start the process, enter following command from within the theme directory:
`npx sharpeye -b chrome --num-retries 0 -t sharpeye.tasks.js -u http://theme.test` for Chrome testing or `npx sharpeye -b firefox --num-retries 0 -t sharpeye.tasks.js -u http://theme.test` for Firefox.

It will make screenshots of the pages, described in `sharpeye.tasks.js` and compare them to the reference images.
If it detects a change, it will output a diff screenshot in `screenshots/diff`.
If you accept this change, move the corresponsing screenshot out of `screenshots/screen` into `screenshots/reference`.

Now commit the new reference image. If you make a pull request on GitHub, you can upload the diff pictures there.
