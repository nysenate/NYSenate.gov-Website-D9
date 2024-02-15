<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Up
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantUp()->run();
 *
 * // with custom path
 * $this->taskVagrantUp('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Up extends Base
{
    protected $action = 'up';

    /**
     * adds `no-provision` option to vagrant up
     *
     * @return $this
     */
    public function noProvision()
    {
        $this->option('--no-provision');

        return $this;
    }

    /**
     * adds `provision` option to vagrant up
     *
     * @return $this
     */
    public function provision()
    {
        $this->option('--provision');

        return $this;
    }

    /**
     * adds `provision-with` option to vagrant up
     *
     * @return $this
     */
    public function provisionWith($provisioners)
    {
        $this->option('--provision-with ' . $provisioners);

        return $this;
    }

    /**
     * adds `no-destroy-on-error` option to vagrant up
     *
     * @return $this
     */
    public function noDestroyonError()
    {
        $this->option('--no-destroy-on-error');

        return $this;
    }

    /**
     * adds `destroy-on-error` option to vagrant up
     *
     * @return $this
     */
    public function destroyonError()
    {
        $this->option('--destroy-on-error');

        return $this;
    }

    /**
     * adds `no-parallel` option to vagrant up
     *
     * @return $this
     */
    public function noParallel()
    {
        $this->option('--no-parallel');

        return $this;
    }

    /**
     * adds `parallel` option to vagrant up
     *
     * @return $this
     */
    public function parallel()
    {
        $this->option('--parallel');

        return $this;
    }

    /**
     * adds `provider` option to vagrant up
     *
     * @return $this
     */
    public function provider($provider)
    {
        $this->option('--provider ' . $provider);

        return $this;
    }

    /**
     * adds `no-install-provider` option to vagrant up
     *
     * @return $this
     */
    public function noinstallProvider()
    {
        $this->option('--no-install-provider');

        return $this;
    }

    /**
     * adds `install-provider` option to vagrant up
     *
     * @return $this
     */
    public function installProvider()
    {
        $this->option('--install-provider');

        return $this;
    }

    public function run()
    {
        $this->printTaskInfo('Vagrant up: ' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
