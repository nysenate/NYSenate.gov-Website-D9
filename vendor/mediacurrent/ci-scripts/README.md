# ci-scripts
Drupal management tasks for Robo Task Runner

This package provides Robo tasks for the following vagrant commands:

        console                             Drupal Console command.
        drush                               Drush command.
        help                                Displays help for a command
        list                                Lists commands
       database
        database:import                     Database Import command.
       project
        project:create-drush-alias          Project task - Create Drush Alias.
        project:init                        Project Init task.
       release
        release:build                       Release Build command.
        release:deploy                      Release Deploy Command.
       site
        site:build                          Site Build command.
        site:install                        Site Install command.
        site:test                           Site Test command.
        site:update                         Site Update command.
       test
        test:php-unit-custom-modules-tests  Run PHPUnit Unit tests on custom modules.
        test:php-unit-tests                 Run all PHPUnit unit tests.
       theme
        theme:build                         Theme Build command.
        theme:compile                       Theme Compile command.
        theme:style-guide                   Theme Style Guide command.
        theme:watch                         Theme Watch command.
       vagrant
        vagrant:check                       Vagrant check - Install plugins, update box, check version.
       watch
        watch:custom-modules                Run tests on changes to custom modules.



##Installation

```
composer require mediacurrent/ci-scripts
```

It may be necessary to define the package in the repositories section of composer.json:

```
"repositories": [
    {
        "type": "vcs",
        "url": "git@bitbucket.org:mediacurrent/ci-scripts.git"
    }
],
```
##Usage

```
<?php

class RoboFile extends \Robo\Tasks
{

    use \Mediacurrent\CiScripts\Task\loadTasks;

    ...

?>
```

##Example


```
    public function databaseImport($database_file = 'latest.sql')
    {
        $this->taskDatabaseImport()
            ->databaseFile($database_file)
            ->run();
    }
```
## Predefined Commands

```
<?php

class RoboFile extends \Robo\Tasks
{

    use \Mediacurrent\CiScripts\Task\loadTasks;
    use \Mediacurrent\CiScripts\Command\Console;
    use \Mediacurrent\CiScripts\Command\Drush;
    use \Mediacurrent\CiScripts\Command\Database;
    use \Mediacurrent\CiScripts\Command\Project;
    use \Mediacurrent\CiScripts\Command\Release;
    use \Mediacurrent\CiScripts\Command\Site;
    use \Mediacurrent\CiScripts\Command\Theme;
    use \Mediacurrent\CiScripts\Command\Vagrant;
    use \JoeStewart\RoboDrupalVM\Task\loadTasks;

    ...

?>
```

Now list the avaliable commands using
```
./vendor/bin/robo list
```


##Credit

Thanks to Robo.li, greg-1-anderson and boedah for example robo code.

