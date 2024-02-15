<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant ListCommands
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantListCommands()->run();
 *
 * // with custom path
 * $this->taskVagrantListCommands('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class ListCommands extends Base
{
    protected $action = 'list-commands';

    public function run()
    {
        $this->printTaskInfo('vagrant list-commands' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
