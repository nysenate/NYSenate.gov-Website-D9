<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Common\ExecOneCommand;
use Robo\Result;
use Robo\Common\ResourceExistenceChecker;
use Robo\Common\Timer;
use Robo\Common\TaskIO;

class ProjectInit extends \Mediacurrent\CiScripts\Task\Base
{
    use ResourceExistenceChecker;
    use \JoeStewart\RoboDrupalVM\Task\loadTasks;
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\File\Tasks;
    use \Robo\Task\FileSystem\Tasks;
    use \Robo\Task\Remote\Tasks;

    public function __construct()
    {
        $this->startTimer();
        parent::__construct();
    }

    public function createProfile($name)
    {

        $name = strtolower($name);

        $custom_profiles_directory = $this->getWebRoot() . '/profiles/custom';

        $profile_directory = $custom_profiles_directory . '/' . $name;

        if (is_dir($profile_directory)) {
            $this->printTaskError("\nRequested profile already exists at " . $profile_directory);
            exit(1);
        }

        // Ensure the profile directory exists.
        if (!is_dir($custom_profiles_directory)) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->mkdir($custom_profiles_directory)
                ->run();
        }

        $template_profile_name = "mis_profile";
        $template_profile_directory = $this->getWebRoot() . '/profiles/contrib/' . $template_profile_name;

        $this->collectionBuilder()->taskWriteToFile($profile_directory . '/' . $name . '.info.yml')
            ->textFromFile($template_profile_directory . '/' . $template_profile_name . '.info.yml')
            ->replace($template_profile_name, $name)
            ->replace("Mediacurrent Profile", $name . ' Profile')
            ->run();

        $this->collectionBuilder()->taskWriteToFile($profile_directory . '/' . $name . '.install')
            ->textFromFile($template_profile_directory . '/' . $template_profile_name . '.install')
            ->replace($template_profile_name, $name)
            ->run();

        $this->collectionBuilder()->taskWriteToFile($profile_directory . '/' . $name . '.profile')
            ->textFromFile($template_profile_directory . '/' . $template_profile_name . '.profile')
            ->replace($template_profile_name, $name)
            ->replace('mcprofile', $name)
            ->run();


        $this->collectionBuilder()->taskWriteToFile($profile_directory . '/composer.json')
            ->textFromFile($template_profile_directory . '/composer.json')
            ->replace($template_profile_name, $name)
            ->run();

        $this->collectionBuilder()->taskFilesystemStack()
            ->copy($template_profile_directory . '/LICENSE.txt', $profile_directory . '/LICENSE.txt')
            ->run();

        $this->collectionBuilder()->taskCopyDir([$template_profile_directory . '/config' => $profile_directory . '/config'])
            ->run();
    }

    public function createTheme($opts)
    {

        $name = strtolower($opts['name']);

        if (!strpos($name, '_theme')) {
            $name = $name . '_theme';
        }

        $custom_themes_directory = $this->getWebRoot() . '/themes/custom';

        $theme_directory = $custom_themes_directory . '/' . $name;

        if (is_dir($theme_directory)) {
            $this->printTaskError("\nRequested theme already exists at " . $theme_directory);
            exit(1);
        }

        // Ensure the custom themes directory exists.
        if (!is_dir($custom_themes_directory)) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->mkdir($custom_themes_directory)
                ->run();
        }

        $template_theme_name = basename($opts['template_theme_directory']);

        $this->collectionBuilder()->taskCopyDir([$this->getWebRoot() . '/' . $opts['template_theme_directory'] => $theme_directory])
        ->run();

        $find_cmd = "find . -type f -print0 | xargs -0 sed -i'' -e 's/$template_theme_name/$name/g'";

        $result = $this->collectionBuilder()->taskExec($find_cmd)
            ->dir($theme_directory)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }

        $template_theme_name_string = ucfirst(str_replace('_theme', '', $template_theme_name));
        $name_string = ucfirst(str_replace('_theme', '', $name));

        $find_cmd = "find . -type f -print0 | xargs -0 sed -i'' -e 's/$template_theme_name_string/$name_string/g'";

        $result = $this->collectionBuilder()->taskExec($find_cmd)
            ->dir($theme_directory)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }

        $find_cmd = "find . -name '*" . $template_theme_name . "*' -exec bash -c 'mv \$0 \${0/" . $template_theme_name . '/' . $name . "}' {} \; ";

        $result = $this->collectionBuilder()->taskExec($find_cmd)
            ->dir($theme_directory)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
    }

    public function drushAlias()
    {

        if (\Drush\Drush::getMajorVersion() >= '9') {
            $vagrant_hostname = explode('.', $this->configuration['vagrant_hostname']);
            $domain_label = $vagrant_hostname[0];

            $drushalias_filename = $domain_label . '.site.yml';
            $drushalias_source = $this->getVendorDir() . '/mediacurrent/ci-scripts/files/example.site.yml.j2';
            $drushalias_dest = $this->getProjectRoot() . '/drush/sites/' . $drushalias_filename;
            if (!is_file($drushalias_dest)) {
                $this->collectionBuilder()->taskFileSystemStack()
                    ->copy($drushalias_source, $drushalias_dest)
                    ->run();
                $this->collectionBuilder()->taskReplaceInFile($drushalias_dest)
                    ->from('example.mcdev')
                    ->to($this->configuration['vagrant_hostname'])
                    ->run();
            }
        } else {
            $drushalias_filename = $this->configuration['vagrant_hostname'] . '.aliases.drushrc.php';
            $drushalias_source = $this->getVendorDir() . '/mediacurrent/ci-scripts/files/example.mcdev.aliases.drushrc.php';
            $drushalias_dest = $this->getProjectRoot() . '/drush/' . $drushalias_filename;
            if (!is_file($drushalias_dest)) {
                $this->collectionBuilder()->taskFileSystemStack()
                    ->copy($drushalias_source, $drushalias_dest)
                    ->run();
                $this->collectionBuilder()->taskReplaceInFile($drushalias_dest)
                    ->from('example.mcdev')
                    ->to($this->configuration['vagrant_hostname'])
                    ->run();
            }
        }

        return $this;
    }

    public function readme()
    {

        $readme_file = $this->getProjectRoot() . '/README.md';
        $readme_template = $this->getVendorDir() . '/mediacurrent/ci-scripts/files/README.md';

        if ($this->useVagrant()) {
            $readme_template = $this->getVendorDir() . '/mediacurrent/ci-scripts/files/README.vagrant.md';
        }

        $result = shell_exec('git ls-remote --get-url');
        $git_remote_url = str_replace("\n", '', $result);

        $bitbucket_remote = explode('/', $git_remote_url);
        $bitbucket_project = $bitbucket_remote[1];

        if (!is_file($readme_file) || !preg_grep("#$git_remote_url#", file($readme_file))) {
            $this->collectionBuilder()->taskWriteToFile($readme_file)
                ->textFromFile($readme_template)
                ->replace('{{ git_remote_url }}', $git_remote_url)
                ->replace('{{ vagrant_hostname }}', $this->configuration['vagrant_hostname'])
                ->replace('{{ bitbucket_project }}', $bitbucket_project)
                ->run();
        }

        return $this;
    }

    public function testsInit($vagrant_hostname = null)
    {
        if (!is_dir($this->getProjectRoot() . '/tests')) {
            $this->collectionBuilder()->taskRsync()
                ->fromPath($this->getVendorDir() . '/mediacurrent/ci-tests/tests')
                ->toPath($this->getProjectRoot())
                ->archive()
                ->verbose()
                ->option('--ignore-existing')
                ->recursive()
                ->run();
        }

        if (!is_file($this->getProjectRoot() . '/tests/behat/behat.local.yml')) {
            $this->collectionBuilder()->taskFileSystemStack()
                ->copy($this->getProjectRoot() . '/tests/behat/behat.local.yml.example', $this->getProjectRoot() . '/tests/behat/behat.local.yml')
                ->run();
            $this->collectionBuilder()->taskReplaceInFile($this->getProjectRoot() . '/tests/behat/behat.local.yml')
                ->from('base_url:')
                ->to('base_url: http://' . $vagrant_hostname)
                ->run();
        }

        if (is_file($this->getProjectRoot() . '/tests/cypress/cypress.config.js')) {
            $this->collectionBuilder()->taskReplaceInFile($this->getProjectRoot() . '/tests/cypress/cypress.config.js')
                ->from('https://mcrain.ddev.site')
                ->to('https://' . $vagrant_hostname)
                ->run();
        }

        if (is_file($this->getProjectRoot() . '/tests/cypress/Makefile')) {
            $this->collectionBuilder()->taskReplaceInFile($this->getProjectRoot() . '/tests/cypress/Makefile')
                ->from('https://mcrain.ddev.site')
                ->to('https://' . $vagrant_hostname)
                ->run();
        }

        if (is_file($this->getProjectRoot() . '/tests/visual-regression/backstop.js')) {
            $this->collectionBuilder()->taskReplaceInFile($this->getProjectRoot() . '/tests/visual-regression/backstop.js')
                ->from('http://example.mcdev')
                ->to('https://' . $vagrant_hostname)
                ->run();
        }
        return $this;
    }

    public function vagrantConfig($vagrant_hostname = null, $vagrant_ip = null)
    {
        if ($vagrant_hostname) {
            $this->collectionBuilder()->taskReplaceInFile($this->getVagrantConfig())
                ->from('example.mcdev')
                ->to($vagrant_hostname)
                ->run();
            $this->collectionBuilder()->taskReplaceInFile($this->getVagrantConfig())
                ->from('example_mcdev')
                ->to(str_replace('.', '_', $vagrant_hostname))
                ->run();
        }
        if ($vagrant_ip) {
            $this->collectionBuilder()->taskReplaceInFile($this->getVagrantConfig())
                ->from('192.168.50.4')
                ->to($vagrant_ip)
                ->run();
        }
        return $this;
    }

    public function vmInit($drupalvm_package)
    {
        $this->collectionBuilder()->taskVmInit()
            ->drupalvmPackage($drupalvm_package)
            ->configFile($this->getVagrantSourceConfig());
        if ($this->useVagrant()) {
            $this->collectionBuilder()->taskVmInit()->vagrantFile($drupalvm_package);
        }
        $this->collectionBuilder()->taskVmInit()->run();

        $this->loadDrupalVMConfiguration();

        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        $this->stopTimer();
        return new Result(
            $this,
            0,
            'ProjectInit',
            ['time' => $this->getExecutionTime()]
        );
    }
}
