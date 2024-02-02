# robo-drupalvm
Drupal VM tasks for Robo Task Runner

[![Build Status](https://travis-ci.org/joestewart/robo-drupalvm.svg?branch=master)](https://travis-ci.org/joestewart/robo-drupalvm) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/52ef2681-fcb8-4bfe-b61d-7c7cf6cf55cc/mini.png)](https://insight.sensiolabs.com/projects/52ef2681-fcb8-4bfe-b61d-7c7cf6cf55cc)

This package provides Robo tasks for the following:

     vm:init - Optionally populates the delegating Vagrantfile and/or config/config.yml.


##Installation

```
composer require joestewart/robo-drupalvm
```
     
##Usage

```
<?php

class RoboFile extends \Robo\Tasks
{

    use \JoeStewart\RoboDrupalVM\Task\loadTasks;
    
    ...
    
?>
```

##Example


```    
    public function vmInit()
    {
        $result = $this->taskVmInit()
            ->configFile()
            ->vagrantFile()
            ->run();
        return $result;
    }
```

## Predefined Commands

```
<?php

class RoboFile extends \Robo\Tasks
{

    use \JoeStewart\RoboDrupalVM\Task\loadTasks;
    use \JoeStewart\RoboDrupalVM\Command\Vm;
    
    ...
    
?>
```

Now list the avaliable commands using
```
./vendor/bin/robo list
```

##Credit

Thanks to Robo.li, greg-1-anderson and boedah for example robo code.

