<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Common\ResourceExistenceChecker;
use Robo\Common\TaskIO;

class SiteUpdate extends \Mediacurrent\CiScripts\Task\Base
{
    use ResourceExistenceChecker;
    use \Robo\Task\Composer\Tasks;
    use \Robo\Task\FileSystem\Tasks;
    use \JoeStewart\RoboDrupalVM\Task\loadTasks;
    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
    use \Mediacurrent\CiScripts\Task\loadTasks;

    public function cacheRebuild()
    {
        $result = $this->collectionBuilder()->taskDrush()
            ->drushCommand('cache-rebuild')
            ->run();
        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function composerInstall()
    {
        $this->collectionBuilder()->taskComposerInstall('composer')
            ->dir($this->getProjectRoot())
            ->run();
        if (!file_exists($this->getProjectRoot() . '/vendor/bin')) {
            $this->collectionBuilder()->taskFilesystemStack()
                ->symlink('../bin', '../vendor/bin')
                ->run();
        }
        return $this;
    }

    public function configImport()
    {
        $result = $this->collectionBuilder()->taskDrush()
            ->drushCommand('config-import')
            ->arg('-y')
            ->run();
        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function siteScaffold()
    {

        $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';

        $multisite = \Robo\Robo::Config()->get('command.site.options.multisite', true);

        $env = \Robo\Robo::Config()->get('command.site.options.env', false);

        $settings_file = \Robo\Robo::Config()->get('command.site.options.settings_file', 'settings.php');

        if ($multisite) {
            $site_directory = $this->getProjectRoot()
                .'/'
                . $webroot
                . '/sites/'
                . $this->configuration['vagrant_hostname'];
        } else {
             $site_directory = $this->getProjectRoot()
                .'/'
                . $webroot
                . '/sites/default';
        }

        // Ensure the site directory exists.
        if (!is_dir($site_directory)) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->mkdir($site_directory)
                ->run();
        }

        if (is_file($site_directory . '/settings.php')) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->chmod($site_directory, 0755)
                ->chmod($site_directory . '/settings.php', 0644)
                ->run();
        } else {
            $this->collectionBuilder()->taskConcat([
                $this->getProjectRoot() . '/' . $webroot . '/sites/default/default.settings.php',
                ])
                ->to($site_directory . '/settings.php')
                ->run();
        }

        if ($settings_file !== 'settings.php' && !is_file($site_directory . '/' . $settings_file)) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->touch($site_directory . '/' . $settings_file)
                ->run();
        } else {
            $this->collectionBuilder()->taskFileSystemStack()
                ->chmod($site_directory, 0755)
                ->chmod($site_directory . '/' . $settings_file, 0644)
                ->run();
        }

        $settings_content = file_get_contents($site_directory . '/' . $settings_file);

        // Populate local env. with contents of sites/example.settings.local.php.
        if (strpos($settings_content, '* To activate this feature, copy and rename it such that its path plus') === false) {
            $this->collectionBuilder()->taskWriteToFile($site_directory . '/services.yml')
                ->textFromFile($this->getProjectRoot() . '/' . $webroot . '/sites/default/default.services.yml')
                ->replace("debug: false", 'debug: true')
                ->run();

            $this->collectionBuilder()->taskWriteToFile($site_directory . '/' . $settings_file)
                ->append(true)
                ->textFromFile($this->getProjectRoot() . '/' . $webroot . '/sites/example.settings.local.php')
                ->run();

            if ($env) {
                $this->collectionBuilder()->taskWriteToFile($site_directory . '/' . $settings_file)
                    ->append(true)
                    ->textFromFile($this->getVendorDir() . '/mediacurrent/ci-scripts/files/settings.env.php.j2')
                    ->run();
            }

            $text = "\n";
            $text .= "if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {\n";
            $text .= "  include \$app_root . '/' . \$site_path . '/settings.local.php';\n";
            $text .= "}";
            $text .= "\n";

            $pattern = '/^  include(.*)settings\.local\.php/sm';

            $this->collectionBuilder()->taskWriteToFile($site_directory . '/settings.php')
                ->append(true)

              ->appendUnlessMatches($pattern, $text)
                ->run();
        }

        if (is_dir($site_directory)) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->chmod($site_directory, 0755)
                ->chmod($site_directory . '/settings.php', 0644)
                ->run();
        }

        if (is_dir($site_directory . '/files')) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->chmod($site_directory . '/files', 0777, 0000, true)
                ->run();
        }
        return $this;
    }

    public function siteThemeBuild($themeDirs = [])
    {
        foreach ($themeDirs as $themeDir) {
            $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';

            $this->pathToTheme = $this->getProjectRoot() .'/' . $webroot . '/' . $themeDir;
            $this->printTaskInfo('pathToTheme = ' . $this->pathToTheme);
            $this->collectionBuilder()->taskThemeBuild()
                ->themeDirectory($this->pathToTheme)
                ->nvmInstall()
                ->nvmUse()
                ->npmInstall()
                ->npmRunBuild()
                ->run();
        }

        return $this;
    }

    public function vagrantUp()
    {

        if (!$this->useVagrant()) {
            return $this;
        }

        if (!is_file($this->getProjectRoot() . 'Vagrantfile')) {
            $this->collectionBuilder()->taskVmInit()
                ->vagrantFile('mediacurrent/mis_vagrant')
                ->run();
        }

        $result = $this->collectionBuilder()->taskVagrantStatus()->printOutput(false)->run()->getMessage();
        if (strpos($result, "The VM is running") === false) {
            $this->collectionBuilder()->taskVagrantUp()->run();
        }
        return $this;
    }

    public function updateDb()
    {
        $result = $this->collectionBuilder()->taskDrush()
            ->drushCommand('updatedb')
            ->arg('-y')
            ->run();
        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        return new Result(
            $this,
            0,
            'SiteInstall',
            ['time' => $this->getExecutionTime()]
        );
    }
}
