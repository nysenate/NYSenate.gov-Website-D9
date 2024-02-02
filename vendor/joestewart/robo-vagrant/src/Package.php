<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Package
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantPackage()->run();
 *
 * // with custom path
 * $this->taskVagrantPackage('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Package extends Base
{
    protected $action = 'package';

    /**
     * adds `base` option to vagrant ssh
     *
     * @return $this
     */
    public function base($base_name = '')
    {
        $this->option('--base "' . $base_name . '"');

        return $this;
    }

    /**
     * adds `output` option to vagrant ssh
     *
     * @return $this
     */
    public function output($output_name = '')
    {
        $this->option('--output "' . $output_name . '"');

        return $this;
    }

    /**
     * adds `include` option to vagrant ssh
     *
     * @return $this
     */
    public function includefile($include_file = '')
    {
        $this->option('--include "' . $include_file . '"');

        return $this;
    }

    /**
     * adds `vagrantfile` option to vagrant ssh
     *
     * @return $this
     */
    public function vagrantfile($vagrantfile_file = '')
    {
        $this->option('--vagrantfile "' . $vagrantfile_file . '"');

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant package' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
