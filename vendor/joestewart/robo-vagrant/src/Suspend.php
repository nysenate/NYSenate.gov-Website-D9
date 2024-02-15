<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Suspend
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantSuspend()->run();
 *
 * // with custom path
 * $this->taskVagrantSuspend('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Suspend extends Base
{
    protected $action = 'suspend';

    public function run()
    {
        $this->printTaskInfo('vagrant suspend' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
