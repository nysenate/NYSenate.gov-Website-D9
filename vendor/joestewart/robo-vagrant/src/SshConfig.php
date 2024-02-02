<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant SshConfig
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantSshConfig()->run();
 *
 * // with custom path
 * $this->taskVagrantSshConfig('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class SshConfig extends Base
{
    protected $action = 'ssh-config';

    /**
     * adds `host` option to vagrant ssh-config
     *
     * @return $this
     */
    public function host($host_name = '')
    {
        $this->option('--host ' . $host_name);

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant ssh-config' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
