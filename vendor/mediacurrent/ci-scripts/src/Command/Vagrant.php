<?php

namespace Mediacurrent\CiScripts\Command;

trait Vagrant
{

    use \JoeStewart\Robo\Task\Vagrant\loadTasks;

    /**
     * Vagrant check - Install plugins, update box, check version.
     *
     * vagrant:check runs the following -
     *
     *  php --version
     *  composer self-update
     *  sw_vers
     *  ansible --version if installed
     *  vagrant box update if required
     *  vagrant --version
     *  vagrant plugin list
     */
    public function vagrantCheck()
    {
        $this->taskVagrantCheck()
          ->phpVersion()
          ->composerUpdate()
          ->osVersion()
          ->ansibleVersion()
          ->boxUpdate()
          ->vagrantVersion()
          ->pluginList()
          ->run();
    }

}
