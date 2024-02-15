<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Snapshot
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantSnapshot()->run();
 *
 * // with custom path
 * $this->taskVagrantSnapshot('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Snapshot extends Base
{
    protected $action = 'snapshot';

    /**
     * adds `delete` subcommand to vagrant snapshot
     *
     * @return $this
     */
    public function delete()
    {
        $this->action = 'snapshot delete';

        return $this;
    }

    /**
     * adds `list` subcommand to vagrant snapshot
     *
     * @return $this
     */
    public function listSnapshots()
    {
        $this->action = 'snapshot list';

        return $this;
    }

    /**
     * adds `pop` subcommand to vagrant snapshot
     *
     * @return $this
     */
    public function pop()
    {
        $this->action = 'snapshot pop';

        return $this;
    }

    /**
     * adds `push` subcommand to vagrant snapshot
     *
     * @return $this
     */
    public function push()
    {
        $this->action = 'snapshot push';

        return $this;
    }

    /**
     * adds `restore` subcommand to vagrant snapshot
     *
     * @return $this
     */
    public function restore()
    {
        $this->action = 'snapshot restore';

        return $this;
    }

    /**
     * adds `save` subcommand to vagrant snapshot
     *
     * @return $this
     */
    public function save()
    {
        $this->action = 'snapshot save';

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant snapshot' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
