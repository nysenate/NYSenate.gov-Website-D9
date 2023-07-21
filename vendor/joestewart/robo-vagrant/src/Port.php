<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Port
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantPort()->run();
 *
 * // with custom path
 * $this->taskVagrantPort('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Port extends Base
{
    protected $action = 'port';

    /**
     * adds `guest` option to vagrant port
     *
     * @return $this
     */
    public function guest($port)
    {
        $this->option('--guest ' . $port);

        return $this;
    }

    /**
     * adds `machine-readable` option to vagrant port
     *
     * @return $this
     */
    public function machineReadable()
    {
        $this->option('--machine-readable');

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant port' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
