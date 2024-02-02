<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Plugin
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantPlugin()->run();
 *
 * // with custom path
 * $this->taskVagrantPlugin('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Plugin extends Base
{
    protected $action = 'plugin';

    /**
     * adds `install` subcommand to vagrant plugin
     *
     * @return $this
     */
    public function install()
    {
        $this->action = 'plugin install';

        return $this;
    }

    /**
     * adds `license` subcommand to vagrant plugin
     *
     * @return $this
     */
    public function license()
    {
        $this->action = 'plugin license';

        return $this;
    }

    /**
     * adds `list` subcommand to vagrant plugin
     *
     * @return $this
     */
    public function listPlugins()
    {
        $this->action = 'plugin list';

        return $this;
    }

    /**
     * adds `uninstall` subcommand to vagrant plugin
     *
     * @return $this
     */
    public function uninstall()
    {
        $this->action = 'plugin uninstall';

        return $this;
    }

    /**
     * adds `update` subcommand to vagrant plugin
     *
     * @return $this
     */
    public function update()
    {
        $this->action = 'plugin update';

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('vagrant plugin' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
