<?php

namespace Mediacurrent\CiScripts\Command;

trait Console
{

    /**
     * Drupal Console command.
     *
     * Wrapper for Drupal Console that adds the uri and root arguments.
     * The uri and root arguments are calculated from the project directory
     * and the config.yml file.
     *
     */
    public function console($console_command = null, $opts = null)
    {

        $this->taskConsole()
            ->consoleCommand($console_command)
            ->consoleOptions($opts)
            ->run();
    }

}
