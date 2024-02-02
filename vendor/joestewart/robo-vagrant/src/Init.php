<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Init
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantInit()->run();
 *
 * // with custom path
 * $this->taskVagrantInit('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Init extends Base
{
    protected $action = 'init';

    /**
     * adds `force` option to vagrant init
     *
     * @return $this
     */
    public function force()
    {
        $this->option('--force');

        return $this;
    }

    /**
     * adds `minimal` option to vagrant init
     *
     * @return $this
     */
    public function minimal()
    {
        $this->option('--minimal');

        return $this;
    }

    /**
     * adds `output` option to vagrant init
     *
     * @return $this
     */
    public function output($output_file)
    {
        $this->option('--output ' . $output_file);

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant init' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
