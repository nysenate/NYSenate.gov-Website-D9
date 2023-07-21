<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Destroy
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantDestroy()->run();
 *
 * // with custom path
 * $this->taskVagrantDestroy('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Destroy extends Base
{
    protected $action = 'destroy';

    /**
     * adds `force` option to vagrant destroy
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
        $this->printTaskInfo('Vagrant destroy: ' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
