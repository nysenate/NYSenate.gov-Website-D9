<?php

namespace JoeStewart\RoboDrupalVM\Task;


use Robo\Result;

class Vm extends \JoeStewart\RoboDrupalVM\Task\Base
{

    /**
     * @return Result
     */
    public function run()
    {
        return new Result(
            $this,
            0,
            'Vm'
        );
    }
}
