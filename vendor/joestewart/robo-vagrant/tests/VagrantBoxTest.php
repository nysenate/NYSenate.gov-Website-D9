<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class VagrantBoxTest extends \PHPUnit\Framework\TestCase implements ContainerAwareInterface
{
    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
    use TaskAccessor;
    use ContainerAwareTrait;

    // Set up the Robo container so that we can create tasks in our tests.
    function setup(): void
    {
        $container = Robo::createDefaultContainer(null, new NullOutput());
        $this->setContainer($container);
    }

    // Scaffold the collection builder
    public function collectionBuilder()
    {
        $emptyRobofile = new \Robo\Tasks;
        return CollectionBuilder::create($this->getContainer(), $emptyRobofile);
    }

    public function testVagrantBoxAddCommand()
    {
        $command = $this->taskVagrantBox('/usr/bin/vagrant')
            ->add()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant box add name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantBoxListCommand()
    {
        $command = $this->taskVagrantBox('/usr/bin/vagrant')
            ->listBoxes()
            ->boxInfo()
            ->getCommand();
        $expected = '/usr/bin/vagrant box list --box-info';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantBoxOutdatedCommand()
    {
        $command = $this->taskVagrantBox('/usr/bin/vagrant')
            ->outdated()
            ->getCommand();
        $expected = '/usr/bin/vagrant box outdated';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantBoxRemoveCommand()
    {
        $command = $this->taskVagrantBox('/usr/bin/vagrant')
            ->remove()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant box remove name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantBoxRepackageCommand()
    {
        $command = $this->taskVagrantBox('/usr/bin/vagrant')
            ->repackage()
            ->arg('name')
            ->arg('provider')
            ->arg('version')
            ->getCommand();
        $expected = '/usr/bin/vagrant box repackage name provider version';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantBoxUpdateCommand()
    {
        $command = $this->taskVagrantBox('/usr/bin/vagrant')
            ->update()
            ->getCommand();
        $expected = '/usr/bin/vagrant box update';
        $this->assertEquals($expected, $command);
    }
}