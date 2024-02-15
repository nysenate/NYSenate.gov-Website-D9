<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Status
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantStatus()->run();
 *
 * // with custom path
 * $this->taskVagrantStatus('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Status extends Base
{
    protected $action = 'status';

    public function run()
    {
        $this->printTaskInfo('vagrant status' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
