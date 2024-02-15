<?php

namespace Mediacurrent\CiScripts\Command;

trait Ddev
{

    /**
     * DDEV command.
     *
     * Wrapper for DDEV cli.
     *
     */
    public function ddev($ddev_command = null, $opts = null)
    {

        $this->taskDdev()
            ->ddevCommand($ddev_command)
            ->ddevOptions($opts)
            ->run();
    }

    /**
     * DDEV config.
     *
     * Wrapper for DDEV cli config commnand.
     *
     * @param array $opts
     * @option $docroot web or docroot.
     * @option $project-type Project type.
     * @option $project-name Project name.
     * @option $webserver-type Webserver type.
     */
    public function ddevConfig(
        $ddev_command = 'config',
        $opts = [
            'docroot' => 'web',
            'project-type' => 'drupal8',
            'project-name' => null,
            'webserver-type' => 'nginx-fpm'
        ]
    ) {

        if(empty($opts['project-name'])) {
            // Get the project root directory name.
            $project_basename = basename(dirname(getcwd(), 1));
            // Lower case and no underscores.
            $project_name = strtolower(str_replace('_', '-', $project_basename));
            $opts['project-name'] = $project_name;
        }

        $opts['create-docroot'] = 'true';

        $this->taskDdev()
            ->ddevCommand($ddev_command)
            ->ddevOptions($opts)
            ->run();
    }

}
