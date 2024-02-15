<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Exception\TaskException;

class Drush extends \Mediacurrent\CiScripts\Task\Base
{
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Remote\Tasks;

    protected $arg;
    protected $drush_command;
    protected $drush_options;
    protected $root;
    protected $uri;

    public function arg($arg = null)
    {
        $this->arg = $arg;

        return $this;
    }

    public function drushCommand($drush_command = null)
    {
        $this->drush_command = $drush_command;

        return $this;
    }

    public function drushOptions($drush_options = null)
    {
        $this->drush_options = $drush_options;

        return $this;
    }

    public function getCommand($pathToInstallDir = null, $uri = null)
    {

        if (!$pathToInstallDir) {
             $pathToInstallDir = $this->configuration['drupal_composer_install_dir'];
        }

        $drush = $pathToInstallDir . '/vendor/bin/drush';
        $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';
        $root = $pathToInstallDir . '/' . $webroot;
        if (!$uri) {
            $uri = 'http://' . $this->configuration['vagrant_hostname'];
        }
        $command = $drush . ' --uri=' . $uri . ' --root=' . $root . ' ' . $this->drush_command;
        if ($this->arg) {
            $command .= ' ' . $this->arg;
        }
        if ($this->drush_options) {
            $command .= ' ' . $this->drush_options;
        }

        return $command;
    }

    public function setConfiguration($configuration)
    {
        if ($configuration) {
            $this->configuration = $configuration;
        }
    }

    /**
     * @return Result
     */
    public function run()
    {

        $command = $this->getCommand();

        $this->printTaskInfo($command);
        $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';
        if ($this->useVagrant()) {
            $result = $this->collectionBuilder()->taskSshExec($this->configuration['vagrant_hostname'], 'vagrant')
                ->remoteDir($this->configuration['drupal_composer_install_dir'] . '/' . $webroot. '/')
                ->exec($command)
                ->identityFile('~/.vagrant.d/insecure_private_key')
                ->run();
        } else {
            $result = $this->collectionBuilder()->taskExec($command)->run();
        }
        return new Result(
            $this,
            $result->getExitCode(),
            'Drupal Drush',
            ['time' => $this->getExecutionTime()]
        );
    }
}
