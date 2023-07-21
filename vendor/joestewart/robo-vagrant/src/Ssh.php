<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Ssh
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantSsh()->run();
 *
 * // with custom path
 * $this->taskVagrantSsh('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Ssh extends Base
{
    protected $action = 'ssh';

    /**
     * adds `command` option to vagrant ssh
     *
     * @return $this
     */
    public function command($command = '')
    {
        $this->option('--command "' . $command . '"');

        return $this;
    }

    /**
     * adds `plain` option to vagrant ssh
     *
     * @return $this
     */
    public function plain()
    {
        $this->option('--plain');

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant ssh' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
