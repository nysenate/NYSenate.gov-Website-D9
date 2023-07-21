<?php

namespace Mediacurrent\CiScripts\Command;

use Robo\Result;
use Symfony\Component\Console\Input\InputOption;

trait Project
{

	/**
     * Project Init task.
     *
     * Ensures config/config.yml exists.
     * Ensures the delegating Vagrantfile is in place.
     * Ensures the tests directory contains the ci-tests contents.
     *
     * @param string $vagrant_hostname Client project local domain [example.mcdev]
     * @param string $vagrant_ip Client project local ip [192.168.50.4]
     *
     */
    public function projectInit($vagrant_hostname = null, $vagrant_ip = null)
    {
        $this->taskProjectInit()
            ->vmInit($this->drupalvm_package)
            ->vagrantConfig($vagrant_hostname, $vagrant_ip)
            ->vmInit($this->drupalvm_package)
            ->testsInit($vagrant_hostname)
            ->readme()
            ->run();
    }

    /**
     * Project task - Create Drush Alias.
     *
     */
    public function projectCreateDrushAlias()
    {

        $this->taskProjectInit()
          ->drushAlias()
          ->run();
    }

    /**
     * Project task - Create Install Profile.
     *
     * Create project custom install profile based on mis_profile.
     *
     * @option $name Specify install profile name. ( Required)
     *
     */
    public function projectCreateProfile(
        $opts = [
            'name' => InputOption::VALUE_REQUIRED,
            'description' => InputOption::VALUE_REQUIRED,
        ]
    )
    {

        if (!empty($opts['name'])) {
            $name = $opts['name'];
        }
        else {
            $this->yell('Name option missing.  Consult help output.', 40, 'red');
            return Result::cancelled();
        }

        $this->taskProjectInit()
          ->createProfile($name)
          ->run();
    }

    /**
     * Project task - Create Custom Theme.
     *
     * Create project custom theme. ( Default is based on ignite_theme.)
     *
     * To base the new theme on the Nimbus theme use the option:
     * "--template_theme_directory=profiles/contrib/rain_demo/themes/nimbus_theme"
     *
     * @option $name Specify custom theme name. ( Required)
     * @option $template_theme_directory Relative path to source theme directory.
     *
     */
    public function projectCreateTheme(
        $opts = [
            'name' => InputOption::VALUE_REQUIRED,
            'template_theme_directory' => 'themes/contrib/ignite_theme'
        ]
    ) {

        if (empty($opts['name'])) {
            $this->yell('Name option missing.  Consult help output.', 40, 'red');
            return Result::cancelled();
        }

        $this->taskProjectInit()
            ->createTheme($opts)
            ->run();
    }

}
