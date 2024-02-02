<?php

namespace JoeStewart\Robo\Task\Vagrant\Command;

trait Vagrant
{

    public function vagrantPhpunitTest()
    {
        $this->stopOnFail(true);
        $this->taskPhpUnit()
            ->option('disallow-test-output')
            ->option('strict-coverage')
            ->option('-v')
            ->option('-d error_reporting=-1')
            ->arg('tests')
            ->run();
    }

    /**
     * Vagrant Box task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantBox($arg = '')
    {
        $this->taskVagrantBox()->arg($arg)->run();
    }

    /**
     * Vagrant Destroy task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantDestroy()
    {
        $this->taskVagrantDestroy()->force()->run();
    }

    /**
     * Vagrant Global Status task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantGlobalStatus()
    {
        $this->taskVagrantGlobalStatus()->run();
    }

    /**
     * Vagrant Help task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantHalt($arg = '')
    {
        $this->taskVagrantHalt()->arg($arg)->run();
    }

    /**
     * Vagrant Help task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantHelp($arg = '')
    {
        $this->taskVagrantHelp()->arg($arg)->run();
    }

    /**
     * Vagrant Init task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantInit()
    {
        $this->taskVagrantInit()->run();
    }

    /**
     * Vagrant List Commands task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantListCommands($arg = '')
    {
        $this->taskVagrantListCommands()->arg($arg)->run();
    }

    /**
     * Vagrant Plugin task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantPlugin($arg = '')
    {
        $this->taskVagrantPlugin()->arg($arg)->run();
    }
 
    /**
     * Vagrant Package task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantPackage($arg = '')
    {
        $this->taskVagrantPackage()->arg($arg)->run();
    }

    /**
     * Vagrant Port task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantPort()
    {
        $this->taskVagrantPort()->run();
    }

    /**
     * Vagrant provision task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantProvision()
    {
        $this->taskVagrantProvision()->run();
    }

    /**
     * Vagrant ssh task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantReload()
    {
        $this->taskVagrantReload()->run();
    }

    /**
     * Vagrant resume task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantResume($arg = '')
    {
        $this->taskVagrantResume()->arg($arg)->run();
    }
 
    /**
     * Vagrant ssh task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantSsh()
    {
        $this->taskVagrantSsh()->run();
    }

    /**
     * Vagrant ssh-config task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantSshConfig()
    {
        $this->taskVagrantSshConfig()->run();
    }

    /**
     * Vagrant Status task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantStatus()
    {
        $this->taskVagrantStatus()->run();
    }

    /**
     * Vagrant Suspend task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantSuspend($arg = '')
    {
        $this->taskVagrantSuspend()->arg($arg)->run();
    }

    /**
     * Vagrant Up task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantUp($arg = '')
    {
        $this->taskVagrantUp()->arg($arg)->run();
    }

    /**
     * Vagrant Status task.
     *
     * @link https://packagist.org/packages/joestewart/robo-vagrant
     *
     */
    public function vagrantVersion()
    {
        $this->taskVagrantVersion()->run();
    }
}
