<?php
namespace JoeStewart\RoboDrupalVM\Task;

trait loadTasks
{
 
    /**
     * @return VmInit
     */
    protected function taskVmInit()
    {
        return $this->task(VmInit::class);
    }

}
