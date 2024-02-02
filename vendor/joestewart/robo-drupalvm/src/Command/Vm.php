<?php

namespace JoeStewart\RoboDrupalVM\Command;

trait Vm
{

	/**
     * Vm Init task.
     *
     */
    public function vmInit()
    {
        $this->taskVmInit()
            ->configFile()
            ->vagrantFile()
            ->run();
    }
}
