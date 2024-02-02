<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Task\BaseTask;
use Robo\Common\ExecOneCommand;
use Robo\Contract\CommandInterface;
use Robo\Contract\TaskInterface;
use Robo\Contract\PrintedInterface;
use Robo\Exception\TaskException;
use Robo\Common\Timer;

class VmConfig extends \Mediacurrent\CiScripts\Task\Base
{
    use Timer;

    private $vagrant_hostname;
    private $vagrant_ip;

    public function __construct($vagrant_hostname = null, $vagrant_ip = null) {

        $this->vagrant_hostname = $vagrant_hostname;
        $this->vagrant_ip = $vagrant_ip;
    }

    /**
     * @return Result
     */
    public function run()
    {
        if($this->vagrant_hostname) {
            $this->taskReplaceInFile($this->getVagrantConfig())
                ->from('example.mcdev')
                ->to($this->$vagrant_hostname)
                ->run();
            $this->taskReplaceInFile($this->getVagrantConfig())
                ->from('example_mcdev')
                ->to(str_replace('.', '_', $this->$vagrant_hostname))
                ->run();
        }
        if($this->vagrant_ip) {
            $this->taskReplaceInFile($this->getVagrantConfig())
                ->from('192.168.50.4')
                ->to($this->$vagrant_ip)
                ->run();
        }
        return new Result(
            $this,
            0,
            'Vm'
        );
    }
}
