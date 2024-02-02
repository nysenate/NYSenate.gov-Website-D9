<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Exception\TaskException;

class Ddev extends \Mediacurrent\CiScripts\Task\Base
{
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Remote\Tasks;

    protected $arg;
    protected $ddev_command;
    protected $ddev_options;
    protected $root;
    protected $uri;

    public function arg($arg = null) {
        $this->arg = $arg;

        return $this;
    }

    public function ddevCommand($ddev_command = null) {

        $this->ddev_command = $ddev_command;

        return $this;
    }

    public function ddevOptions($ddev_options = null) {

        $this->ddev_options = '';
        if(is_array($ddev_options)) {
            foreach ( $ddev_options as $key => $value) {
                if (is_string($value)) {
                    $this->ddev_options .= ' --' . $key . '=' . $value;
                }
            }
        }
        return $this;
    }

    public function getCommand() {

        $ddev = 'ddev';

        $command = $ddev . ' ' . $this->ddev_command;
        if($this->arg) {
            $command .= ' ' . $this->arg;
        }
        if($this->ddev_options) {
            $command .= ' ' . $this->ddev_options;
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

        $result = $this->collectionBuilder()->taskExec($command)
            ->dir(dirname(getcwd(), 1))
            ->run();

        return new Result(
            $this,
            $result->getExitCode(),
            'DDEV',
            ['time' => $this->getExecutionTime()]
        );

    }
}
