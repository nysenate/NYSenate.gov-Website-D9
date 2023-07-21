<?php

class RoboFile extends \Robo\Tasks
{

    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
    use \JoeStewart\Robo\Task\Vagrant\Command\Vagrant;

    public function test()
    {
        $this->stopOnFail(true);
        $this->taskPhpUnit()
            ->option('disallow-test-output')
            ->option('strict-coverage')
            ->option('-v')
            ->option('-d error_reporting=-1')
            ->arg('tests')
            ->run();
    }

    
}
