<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;

class VagrantCheck extends \Mediacurrent\CiScripts\Task\Base
{

    use \JoeStewart\RoboDrupalVM\Task\loadTasks;
    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
    use \Robo\Task\Base\Tasks;

    protected $os_version = 'unknown';

    public function __construct()
    {
        $this->startTimer();
    }

    public function phpVersion()
    {
        if (PHP_VERSION_ID < 50509) {
            $this->printTaskInfo('Some dependencies require php version >= 5.5.9');
        }
        $this->collectionBuilder()->taskExec('php --version')->run();


        return $this;
    }

    public function composerUpdate()
    {
        $this->collectionBuilder()->taskExec('composer self-update')->run();

        return $this;
    }

    public function osVersion()
    {
        if (PHP_OS == 'Darwin') {
            $result = $this->collectionBuilder()->taskExec('sw_vers')->run();
            $this->os_version = $result->getMessage();
        }

        return $this;
    }

    public function vagrantVersion()
    {
        $result = $this->collectionBuilder()->taskVagrantVersion()->run();
        $vagrant_version = $result->getMessage();
        $this->collectionBuilder()->taskExec('VBoxManage --version')->run();

        return $this;
    }

    public function boxUpdate()
    {
        $result = $this->collectionBuilder()->taskVagrantBox()
          ->outdated()
          ->run();

        $vagrant_box_outdated = $result->getMessage();

        if (strpos($vagrant_box_outdated, 'A newer version of the box')) {
            $this->collectionBuilder()->taskVagrantBox()
              ->update()
              ->run();
        }

        return $this;
    }

    public function pluginList()
    {
        $this->collectionBuilder()->taskVagrantPlugin()
         ->listPlugins()
         ->run();

        return $this;
    }

    public function ansibleVersion()
    {
      // Check if Ansible is installed and check the version, if it is.
        $result = shell_exec('command -v ansible');
        if (!empty($result)) {
            $this->collectionBuilder()->taskExec('ansible --version')->run();
        }

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
