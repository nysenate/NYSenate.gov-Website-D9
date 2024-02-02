# robo-vagrant
Vagrant tasks for Robo Task Runner

[![Build Status](https://travis-ci.org/joestewart/robo-vagrant.svg?branch=master)](https://travis-ci.org/joestewart/robo-vagrant)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/800f42d0-51b1-4f51-b6f6-e66ea8af488a/mini.png)](https://insight.sensiolabs.com/projects/800f42d0-51b1-4f51-b6f6-e66ea8af488a)

This package provides Robo tasks for the following vagrant commands:

     box             manages boxes: installation, removal, etc.
     destroy         stops and deletes all traces of the vagrant machine
     global-status   outputs status Vagrant environments for this user
     halt            stops the vagrant machine
     help            shows the help for a subcommand
     init            initializes a new Vagrant environment by creating a Vagrantfile
     plugin          manages plugins: install, uninstall, update, etc.
     port            displays information about guest port mappings
     package         packages a running vagrant environment into a box
     provision       provisions the vagrant machine
     reload          restarts vagrant machine, loads new Vagrantfile configuration
     resume          resume a suspended vagrant machine
     ssh             connects to machine via SSH
     ssh-config      outputs OpenSSH valid configuration to connect to the machine
     status          outputs status of the vagrant machine
     suspend         suspends the machine
     up              starts and provisions the vagrant environment
     version         prints current and latest Vagrant version



##Installation

```
composer require joestewart/robo-vagrant
```
     
##Usage

```
<?php

class RoboFile extends \Robo\Tasks
{

    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
    
    ...
    
?>
```

##Example


```    
    public function vagrantUp($arg = '')
    {
        $result = $this->taskVagrantUp()->arg($arg)->run();
        return $result;
    }
```

## Predefined Commands

```
<?php

class RoboFile extends \Robo\Tasks
{

    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
    use \JoeStewart\Robo\Task\Vagrant\Command\Vagrant;
    
    ...
    
?>
```

Now list the avaliable commands using
```
./vendor/bin/robo list
```

##Credit

Thanks to Robo.li, greg-1-anderson and boedah for example robo code.
