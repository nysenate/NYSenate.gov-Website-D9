<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Exception\TaskException;

class Console extends \Mediacurrent\CiScripts\Task\Base
{
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Remote\Tasks;

    protected $arg;
    protected $console_command;
    protected $console_options;
    protected $root;
    protected $uri;

    public function arg($arg = null) {
        $this->arg = $arg;

        return $this;
    }

    public function consoleCommand($console_command = null) {
        $this->console_command = $console_command;

        return $this;
    }

    public function consoleOptions($console_options = null) {
        $this->console_options = $console_options;

        return $this;
    }

    public function getCommand($pathToInstallDir = null, $uri = null) {

        if (!$pathToInstallDir) {
             $pathToInstallDir = $this->configuration['drupal_composer_install_dir'];
        }

        $console = $pathToInstallDir . '/bin/drupal';
        $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';
        $root = $pathToInstallDir . '/' . $webroot;
        if(!$uri) {
            $uri = 'http://' . $this->configuration['vagrant_hostname'];
        }
        $command = $console . ' --uri=' . $uri . ' --root=' . $root . ' ' . $this->console_command;
        if($this->arg) {
            $command .= ' ' . $this->arg;
        }
        if($this->console_options) {
            $command .= ' ' . $this->console_options;
        }

        return $command;
    }

    public function setConfiguration($configuration) {
        if($configuration) {
            $this->configuration = $configuration;
        }
    }

    /**
     * @return Result
     */
    public function run() {

        $command = $this->getCommand();

        $this->printTaskInfo($command);

        $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';

        if($this->useVagrant()) {
            $result = $this->collectionBuilder()->taskSshExec($this->configuration['vagrant_hostname'], 'vagrant')
                ->remoteDir($this->configuration['drupal_composer_install_dir'] . '/' . $webroot. '/')
                ->exec($command)
                ->identityFile('~/.vagrant.d/insecure_private_key')
                ->run();
        }
        else {
            $result = $this->collectionBuilder()->taskExec($command)
                ->dir($this->configuration['drupal_composer_install_dir'] . '/' . $webroot. '/')
                ->run();
        }
        return new Result(
            $this,
            $result->getExitCode(),
            'Drupal Console',
            ['time' => $this->getExecutionTime()]
        );

    }
}
