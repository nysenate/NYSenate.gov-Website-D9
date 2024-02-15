<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Version
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantVersion()->run();
 *
 * // with custom path
 * $this->taskVagrantVersion('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Version extends Base
{
    protected $action = 'version';

    public function run()
    {
        $this->printTaskInfo('Vagrant version: ' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
