<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Box
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantBox()->run();
 *
 * // with custom path
 * $this->taskVagrantBox('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Box extends Base
{
    protected $action = 'box';

    /**
     * adds `add` subcommand to vagrant box
     *
     * @return $this
     */
    public function add()
    {
        $this->action = 'box add';

        return $this;
    }

    /**
     * adds `box-info` option to vagrant box
     *
     * @return $this
     */
    public function boxInfo()
    {
        $this->option('--box-info');

        return $this;
    }

    /**
     * adds `list` subcommand to vagrant box
     *
     * @return $this
     */
    public function listBoxes()
    {
        $this->action = 'box list';

        return $this;
    }

    /**
     * adds `outdated` subcommand to vagrant box
     *
     * @return $this
     */
    public function outdated()
    {
        $this->action = 'box outdated';

        return $this;
    }

    /**
     * adds `remove` subcommand to vagrant box
     *
     * @return $this
     */
    public function remove()
    {
        $this->action = 'box remove';

        return $this;
    }

    /**
     * adds `repackage` subcommand to vagrant box
     *
     * @return $this
     */
    public function repackage()
    {
        $this->action = 'box repackage';

        return $this;
    }

    /**
     * adds `update` subcommand to vagrant box
     *
     * @return $this
     */
    public function update()
    {
        $this->action = 'box update';

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant box' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
