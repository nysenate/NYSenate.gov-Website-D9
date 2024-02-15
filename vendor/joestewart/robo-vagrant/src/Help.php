<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Help
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantHelp()->run();
 *
 * // with custom path
 * $this->taskVagrantHelp('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Help extends Base
{
    protected $action = 'help';

    public function run()
    {
        $this->printTaskInfo('vagrant help' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
