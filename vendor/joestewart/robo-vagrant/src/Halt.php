<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Halt
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantHalt()->run();
 *
 * // with custom path
 * $this->taskVagrantHalt('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Halt extends Base
{
    protected $action = 'halt';

    /**
     * adds `force` option to vagrant halt
     *
     * @return $this
     */
    public function force()
    {
        $this->option('--force');

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant halt' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
