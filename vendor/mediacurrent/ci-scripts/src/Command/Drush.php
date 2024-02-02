<?php

namespace Mediacurrent\CiScripts\Command;

trait Drush
{

    /**
     * Drush command.
     *
     * Wrapper for Drush that adds the uri and root arguments.
     * The uri and root arguments are calculated from the project directory
     * and the config.yml file.
     *
     */
    public function drush($drush_command = null, $opts = null)
    {

        $this->taskDrush()
            ->drushCommand($drush_command)
            ->drushOptions($opts)
            ->run();
    }

}
