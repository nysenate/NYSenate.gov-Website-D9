<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Common\ResourceExistenceChecker;
use Robo\Common\Timer;
use Robo\Common\TaskIO;

class SiteInstall extends \Mediacurrent\CiScripts\Task\Base
{
    use ResourceExistenceChecker;

    /**
     * @return Result
     */
    public function run()
    {
        $this->startTimer();
        chdir($this->getWebRoot());

        $multisite = \Robo\Robo::Config()->get('command.site.options.multisite', true);

        $env = \Robo\Robo::Config()->get('command.site.options.env', false);

        $drupal_install_profile = \Robo\Robo::Config()->get('drupal_install_profile', false);

        $existing_config = \Robo\Robo::Config()->get('existing_config', false);

        if (!empty($this->configuration['drupal_db_name'])) {
            $db_name = $this->configuration['drupal_db_name'];
        } else {
            $db_name = $this->configuration['vagrant_machine_name'];
        }

        $drupal_db_host = (isset($this->configuration['drupal_db_host'])) ? $this->configuration['drupal_db_host'] : 'localhost';

        if (!empty($this->configuration['drupal_mysql_user'])) {
            // deprecated - drupal_mysql_* is used in mis_vagrant < 3.3.0.
            $dbconnection_string = $this->configuration['drupal_mysql_user']
                .':'
                . $this->configuration['drupal_mysql_password']
                . '@' . $drupal_db_host . '/'
                . $db_name;
            $drupal_db_user = $this->configuration['drupal_mysql_user'];
            $drupal_db_password = $this->configuration['drupal_mysql_password'];
        } else {
            $dbconnection_string = $this->configuration['drupal_db_user']
                .':'
                . $this->configuration['drupal_db_password']
                . '@' . $drupal_db_host . '/'
                . $db_name;
            $drupal_db_user = $this->configuration['drupal_db_user'];
            $drupal_db_password = $this->configuration['drupal_db_password'];
        }

        if ($multisite) {
            $sites_subdir = \Robo\Robo::Config()->get('sites_subdir', false);
            if (!$sites_subdir) {
                $sites_subdir = $this->configuration['vagrant_hostname'];
            }
        }
        else {
            $sites_subdir = 'default';
        }

        $env_file = $this->getProjectRoot() . '/.env';
        $env_template = $this->getVendorDir() . '/mediacurrent/ci-scripts/files/env.j2';

        if ($env && !is_file($env_file)) {
            $this->collectionBuilder()->taskWriteToFile($env_file)
                ->textFromFile($env_template)
                ->replace("{{ drupal_db_name }}", $db_name)
                ->replace("{{ drupal_db_host }}", $drupal_db_host)
                ->replace("{{ drupal_db_password }}", $drupal_db_password)
                ->replace("{{ drupal_db_user }}", $drupal_db_user)
                ->run();
        }

        $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';

        $bin_dir = str_replace("\n", '', shell_exec('cd .. && composer config bin-dir'));
        $drush =  '../' . $bin_dir . '/drush';

        $drush_opts = $drupal_install_profile;
        $drush_opts .= " -r " . $this->configuration['drupal_composer_install_dir'] . '/' . $webroot . '/';
        $drush_opts .= " --sites-subdir=" . $sites_subdir;
        $drush_opts .= " --db-url=mysql://" . $dbconnection_string;
        $drush_opts .= ' --site-name="' . $this->configuration['drupal_site_name'] . '"';
        $drush_opts .= " --site-mail=admin@example.com";
        $drush_opts .= " --account-mail=admin@example.com";
        $drush_opts .= " --account-name=" . $this->configuration['drupal_account_name'];
        $drush_opts .= " --account-pass=" . $this->configuration['drupal_account_pass'];
        if($existing_config)
        {
            $drush_opts .= " --existing-config";
        }

        $siteinstallTask = $this->collectionBuilder()->taskDrush()
            ->drushCommand('site:install')
            ->drushOptions($drush_opts)
            ->arg('-y');

        if ($this->useVagrant()) {
            $result = $this->collectionBuilder()->taskSshExec($this->configuration['vagrant_hostname'], 'vagrant')
                ->remoteDir($this->configuration['drupal_composer_install_dir'] . '/' . $webroot. '/')
                ->exec($siteinstallTask)
                ->identityFile('~/.vagrant.d/insecure_private_key')
                ->run();
        } else {
            $result = $siteinstallTask->run();
        }

       if($env) {
            $db_settings = "\n\$databases['default']['default'] = array (\n";
            $db_settings .= "  'database' => '" . $db_name . "',\n";
            $db_settings .= "  'username' => '" . $drupal_db_user . "',\n";
            $db_settings .= "  'password' => '" . $drupal_db_password . "',\n";
            $db_settings .= "  'prefix' => '',\n";
            $db_settings .= "  'host' => '" . $drupal_db_host . "',\n";
            $db_settings .= "  'port' => '',\n";
            $db_settings .= "  'namespace' => 'Drupal\\\\Core\\\\Database\\\\Driver\\\\mysql',\n";
            $db_settings .= "  'driver' => 'mysql',\n";
            $db_settings .= ");\n";

            $this->collectionBuilder()->taskReplaceInFile($this->getWebRoot() . '/sites/' . $sites_subdir . '/settings.php')
                ->from($db_settings)
                ->to("\n")
                ->run();
        }

        $this->stopTimer();
        return new Result(
            $this,
            $result->getExitCode(),
            'SiteInstall',
            ['time' => $this->getExecutionTime()]
        );

    }
}
