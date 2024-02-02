<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Provision
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantProvision()->run();
 *
 * // with custom path
 * $this->taskVagrantProvision('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Provision extends Base
{
    protected $action = 'provision';

    /**
     * adds `provision-with` option to vagrant provision
     *
     * @return $this
     */
    public function provisionWith($provisioners)
    {
        $this->option('--provision-with ' . $provisioners);

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant provision' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
