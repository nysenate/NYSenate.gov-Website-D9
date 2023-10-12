#Installation Using Composer (recommended)

If you use Composer to manage dependencies, edit your site's "composer.json"
file as follows.

1. Add the asset-packagist composer repository to "composer.json".
This allows installing packages (like Chart.js) that are published on NPM.

        "asset-packagist": {
            "type": "composer",
            "url": "https://asset-packagist.org"
        },

You may need to add it in your composer.json file like this (second item in
the array):

        "repositories": [
            {
                "type": "composer",
                "url": "https://packages.drupal.org/8"
            },
            {
                "type": "composer",
                "url": "https://asset-packagist.org"
            },
        ],

2. Run the following command to ensure that you have the
"oomphinc/composer-installers-extender" package installed. This package
facilitates the installation of any package into directories other than the
default "/vendor" (e.g. "/libraries") using Composer.

        composer require --prefer-dist oomphinc/composer-installers-extender

3. Configure composer to install the Chart.js dependencies into "/libraries"
by adding the following "installer-types" and "installer-paths" to the "extra"
section of "composer.json". If you are not using the "web" directory, then
remove "web/" from the lines below:

"extra": {
    "installer-types": ["npm-asset"],
    "installer-paths": {
        "web/libraries/chartjs": ["npm-asset/chart.js"],
        "web/libraries/chartjs-adapter-date-fns": [
          "npm-asset/chartjs-adapter-date-fns"
          ],
    },
}

4. This and the following step is optional but recommended. The reason for
them is that when installing the Chart.js package with Composer,
additional files are added into the library directory. These files are not
necessary and can be potentially harmful to your site, so it's best to remove
them. So: create a new directory in your project root called "scripts".

5. Inside that directory, create a new file called "clean-chartjs.sh" and
   paste the following into it:

        #!/usr/bin/env bash
        set -eu
        declare -a directories=(
          "web/libraries/chartjs/auto"
          "web/libraries/chartjs/helpers"
          "web/libraries/chartjs/types"
          "web/libraries/chartjs/dist/chunks"
          "web/libraries/chartjs/dist/docs"
        )
        counter=0
        echo "Deleting unneeded directories inside web/libraries/chartjs"
        for directory in "${directories[@]}"
          do
            if [ -d $directory ]; then
              echo "Deleting $directory"
              rm -rf $directory
              counter=$((counter+1))
            fi
          done
        echo "$counter folders were deleted"
        declare -a files=(
          "web/libraries/chartjs/README.md"
          "web/libraries/chartjs/LICENSE.md"
          "web/libraries/chartjs/package.json"
          "web/libraries/chartjs/dist/helpers.esm.js"
          "web/libraries/chartjs/dist/helpers.mjs"
          "web/libraries/chartjs/dist/chart.mjs"
          "web/libraries/chartjs/dist/chart.esm.js"
          "web/libraries/chartjs-adapter-date-fns/README.md"
          "web/libraries/chartjs-adapter-date-fns/LICENSE.md"
          "web/libraries/chartjs-adapter-date-fns/package.json"
          "web/libraries/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.esm.js"
        )
        counter=0
        echo "Deleting unneeded files inside web/libraries/chartjs"
        for file in "${files[@]}"
          do
            if [[ -f $file ]]; then
              echo "Deleting $file"
              rm $file
              counter=$((counter+1))
            fi
          done
        echo "$counter files were deleted"

6. Add a "scripts" entry to your "composer.json" file as shown below. If
   "scripts" already exists, you will need to do a little more to incorporate
   the code below.

  "scripts": {
      "clean-chartjs": "chmod +x scripts/clean-chartjs.sh &&
      ./scripts/clean-chartjs.sh",
      "post-install-cmd": [
        "@clean-chartjs"
      ],
      "post-update-cmd": [
        "@clean-chartjs"
      ]
  }

7. Run the following command; you should find that new directories have been
   created under "/libraries".

    composer require --prefer-dist npm-asset/chart.js:^3.3
    npm-asset/chartjs-adapter-date-fns:^2
