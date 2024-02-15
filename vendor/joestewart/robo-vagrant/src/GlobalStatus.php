<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant GlobalStatus
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantGlobalStatus()->run();
 *
 * // with custom path
 * $this->taskVagrantGlobalStatus('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class GlobalStatus extends Base
{
    protected $action = 'global-status';

    public function run()
    {
        $this->printTaskInfo('vagrant global-status' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
