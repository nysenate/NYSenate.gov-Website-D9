<?php

namespace Mediacurrent\CiScripts\Command;

use Symfony\Component\Console\Input\InputOption;

trait Site
{

    /**
     * Site Build command.
     *
     * site:build runs the following -
     *
     *  composer install
     *  vagrant up if required
     *  Ensures sites/example.mcdev and settings.php are writable
     *  drush site-install
     *  Ensures sites/example.mcdev/files is writable
     *
     * @option $theme_build Runs default theme build task.  Specify relative path to theme from the webroot.
     * @option $drupal_install_profile Install using specified install profile. Use -Ddrupal_install_profile to override.
     * @option $existing_config Configuration from sync directory should be imported during installation. Use -Dexisting_config to override.
     */
    public function siteBuild(
        $opts = [
            'theme_build' => []
        ]
    ) {

        $this->taskSiteBuild()
            ->composerInstall()
            ->vagrantUp()
            ->siteInstall()
            ->siteThemeBuild($opts['theme_build'])
            ->run();
    }

    /**
     * Site Install command.
     *
     * site:install runs drush site-install with configuration from config/config.yml
     *
     * @option $drupal_install_profile Install using specified install profile. Use -Ddrupal_install_profile to override.
     * @option $existing_config Configuration from sync directory should be imported during installation. Use -Dexisting_config to override.
     */
    public function siteInstall()
    {
        $this->taskSiteInstall()->run();
    }

    /**
     * Site Test command.
     *
     * site:test runs the requested tests on the site
     *
     * Options:
     *
     * --phpcs
     * Run Drupal coding standards via code sniiffer.
     *
     * Accepts an argument that contains the absolute path to the directory
     * to be tested.  Defaults to "modules/custom".
     *
     * Example usage:
     *   "--phpcs $(pwd)/web/profiles/custom"
     *
     * @param array $opts
     * @option $behat Run behat tests.
     * @option $pa11y Run pa11y accessibility tests.
     * @option $phpunit Run phpunit tests.
     * @option $phpcs Run Drupal coding standards via code sniiffer.
     *
     */
    public function siteTest(
        $test_argument = null,
        $opts = [
            'behat' => false,
            'pa11y' => false,
            'phpunit' => false,
            'phpcs' => false
        ]
    ) {
        $this->taskSiteTest()
          ->testArgument($test_argument)
          ->testOptions($opts)
          ->run();
    }

    /**
     * Site Update command.
     *
     * site:update runs the following -
     *
     *  composer install
     *  vagrant up if required
     *  drush updatedb
     *  drush config-import
     *  drush cache-rebuild
     *
     * @option $theme_build Runs default theme build task. Specify relative path to theme from the webroot.
     */
    public function siteUpdate($opts = [
            'theme_build' => []
        ]
    ) {
        $this->taskSiteUpdate()
          ->composerInstall()
          ->vagrantUp()
          ->siteScaffold()
          ->updateDB()
          ->configImport()
          ->cacheRebuild()
          ->siteThemeBuild($opts['theme_build'])
          ->run();
    }
}
